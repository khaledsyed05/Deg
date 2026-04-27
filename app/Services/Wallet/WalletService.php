<?php

namespace App\Services\Wallet;

use App\Enums\CreditType;
use App\Enums\PaymentFlowType;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Promotion;
use App\Models\PromoUsage;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\WithdrawalRequest;
use App\Notifications\WalletTopupFailed;
use App\Notifications\WalletTopupSucceeded;
use App\Services\Wallet\Gateways\WalletTopupGatewayFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletService
{
    public const MIN_TOPUP = 50_000;

    public const MAX_TOPUP = 5_000_000;

    public const DAILY_TOPUP_LIMIT = 10_000_000;

    public const MIN_TRANSFER = 10_000;

    public const MAX_TRANSFER = 500_000;

    public const DAILY_TRANSFER_LIMIT = 1_000_000;

    public const TRANSFER_FEE_PCT = 0.02;

    public const MIN_WITHDRAW = 100_000;

    public const MAX_WITHDRAW = 2_000_000;

    public const WITHDRAW_FEE_PCT = 0.03;

    public const WITHDRAW_FEE_MIN = 5_000;

    public function __construct(
        private ?WalletTopupGatewayFactory $gatewayFactory = null,
    ) {}

    /**
     * Initiate a real wallet top-up via the chosen payment provider.
     * Creates a pending Payment record and starts the gateway flow.
     * Wallet credit happens only on confirmTopup() / handleBankCallback().
     *
     * @return array<string, mixed>
     */
    public function initiateTopup(User $user, int $amount, string $method, ?string $phone = null): array
    {
        $this->validateTopupAmount($amount);
        $this->checkDailyTopupCap($user, $amount);

        if (in_array($method, ['syriatel', 'mtn'], true) && empty($phone)) {
            throw new \RuntimeException('رقم الهاتف مطلوب لهذه الطريقة');
        }

        $bonus = $this->calculateBonus($amount);
        $totalCredit = $amount + $bonus;
        $wallet = Wallet::forUser($user);
        $factory = $this->gatewayFactory();
        $provider = $factory->methodToProvider($method);
        $gateway = $factory->resolve($method);
        $flowType = $method === 'bank' ? PaymentFlowType::Webview : PaymentFlowType::Otp;

        return DB::transaction(function () use ($user, $wallet, $amount, $bonus, $totalCredit, $method, $phone, $provider, $gateway, $flowType) {
            $payment = Payment::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'booking_id' => null,
                'payment_type' => 'wallet_topup',
                'amount' => $amount,
                'bonus_amount' => $bonus,
                'total_credited' => $totalCredit,
                'currency' => $wallet->currency ?: 'SYP',
                'provider' => $provider,
                'flow_type' => $flowType,
                'status' => PaymentStatus::Pending,
                'initiated_at' => now(),
                'provider_meta' => ['method' => $method, 'phone' => $phone],
            ]);

            try {
                $result = $gateway->initiate($payment, [
                    'phone' => (string) $phone,
                    'amount' => $amount,
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                ]);
            } catch (\Throwable $e) {
                $payment->markFailed($e->getMessage());
                Log::channel('payments')->error('wallet_topup.initiate_failed', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
                throw new \RuntimeException('فشل بدء عملية الشحن: '.$e->getMessage());
            }

            $payment->update([
                'provider_reference' => $result->providerReference,
                'provider_payload' => $result->metadata,
            ]);

            return [
                'payment_id' => $payment->id,
                'method' => $method,
                'amount' => $amount,
                'bonus' => $bonus,
                'total_credit' => $totalCredit,
                'status' => $payment->status->value,
                'next_step' => $result->nextStep,
                'data' => $result->responseData,
            ];
        });
    }

    /**
     * Confirm an OTP-based top-up (Syriatel/MTN). Credits the wallet on success.
     *
     * @return array<string, mixed>
     */
    public function confirmTopup(int $paymentId, string $otp, User $user): array
    {
        $payment = Payment::query()
            ->where('id', $paymentId)
            ->where('user_id', $user->id)
            ->where('payment_type', 'wallet_topup')
            ->where('status', PaymentStatus::Pending)
            ->firstOrFail();

        $factory = $this->gatewayFactory();
        $method = $payment->provider_meta['method'] ?? $payment->provider->value;
        $gateway = $factory->resolve($method);

        $result = $gateway->confirm($payment, $otp);

        if (! $result->success) {
            $payment->markFailed($result->errorMessage);
            $this->safeNotify($user, new WalletTopupFailed($payment));
            throw new \RuntimeException($result->errorMessage ?: 'فشل تأكيد الدفع');
        }

        return $this->finalizeSuccessfulTopup($payment, $result->transactionId, $result->providerData);
    }

    /**
     * Resend OTP for a pending top-up.
     */
    public function resendTopupOtp(int $paymentId, User $user): bool
    {
        $payment = Payment::query()
            ->where('id', $paymentId)
            ->where('user_id', $user->id)
            ->where('payment_type', 'wallet_topup')
            ->where('status', PaymentStatus::Pending)
            ->firstOrFail();

        $factory = $this->gatewayFactory();
        $method = $payment->provider_meta['method'] ?? $payment->provider->value;

        return $factory->resolve($method)->resendOtp($payment);
    }

    /**
     * Cancel a pending top-up payment.
     */
    public function cancelTopup(int $paymentId, User $user): Payment
    {
        $payment = Payment::query()
            ->where('id', $paymentId)
            ->where('user_id', $user->id)
            ->where('payment_type', 'wallet_topup')
            ->where('status', PaymentStatus::Pending)
            ->firstOrFail();

        $payment->update(['status' => PaymentStatus::Cancelled]);

        Log::channel('payments')->info('wallet_topup.cancelled', ['payment_id' => $payment->id]);

        return $payment;
    }

    /**
     * Webhook entry-point for bank (AlBaraka) callbacks. Idempotent.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function handleBankCallback(array $payload): array
    {
        $factory = $this->gatewayFactory();
        $gateway = $factory->resolve('bank');

        $result = $gateway->handleCallback($payload);
        $reference = $result->transactionId ?? ($payload['idTransaction'] ?? $payload['transactionReference'] ?? null);

        $payment = Payment::query()
            ->where('payment_type', 'wallet_topup')
            ->where('provider_reference', $reference)
            ->first();

        if (! $payment) {
            Log::channel('payments')->warning('wallet_topup.bank.unknown_reference', ['reference' => $reference]);
            throw new \RuntimeException('Payment not found');
        }

        // Idempotency
        if ($payment->status === PaymentStatus::Completed) {
            return ['success' => true, 'idempotent' => true, 'payment_id' => $payment->id];
        }

        if (! $result->success) {
            $payment->markFailed($result->errorMessage);
            $this->safeNotify($payment->user, new WalletTopupFailed($payment));

            return ['success' => false, 'reason' => $result->errorMessage];
        }

        $this->finalizeSuccessfulTopup($payment, $result->transactionId, $result->providerData);

        return ['success' => true, 'payment_id' => $payment->id];
    }

    /**
     * Apply credit + bonus to wallet, mark payment completed, notify user.
     *
     * @param  array<string, mixed>  $providerData
     * @return array<string, mixed>
     */
    protected function finalizeSuccessfulTopup(Payment $payment, ?string $transactionId, array $providerData): array
    {
        return DB::transaction(function () use ($payment, $transactionId, $providerData) {
            $wallet = $payment->wallet ?? Wallet::forUser($payment->user);

            $payment->update([
                'status' => PaymentStatus::Completed,
                'completed_at' => now(),
                'provider_transaction_id' => $transactionId ?: $payment->provider_transaction_id,
                'provider_meta' => array_merge($payment->provider_meta ?? [], $providerData),
            ]);

            $topupTx = $wallet->credit(
                (int) $payment->amount,
                CreditType::TOPUP,
                "Top-up via {$payment->provider->value}",
                $payment,
                null,
                ['payment_id' => $payment->id, 'method' => $payment->provider->value],
            );

            $bonusTx = null;
            if ($payment->bonus_amount > 0) {
                $expiresAt = now()->addDays((int) config('wallet.bonus_credits_expire_after_days', 60));
                $bonusTx = $wallet->credit(
                    (int) $payment->bonus_amount,
                    CreditType::BONUS,
                    'مكافأة شحن',
                    $payment,
                    $expiresAt,
                    ['payment_id' => $payment->id, 'topup_amount' => $payment->amount],
                );
            }

            $this->safeNotify($payment->user, new WalletTopupSucceeded($payment));

            return [
                'payment_id' => $payment->id,
                'status' => $payment->status->value,
                'amount_paid' => (int) $payment->amount,
                'bonus' => (int) $payment->bonus_amount,
                'total_credited' => (int) ($payment->total_credited ?? ($payment->amount + $payment->bonus_amount)),
                'new_balance' => $wallet->fresh()->balance,
                'topup_transaction_id' => $topupTx->id,
                'bonus_transaction_id' => $bonusTx?->id,
            ];
        });
    }

    public function calculateBonus(int $amount): int
    {
        foreach ((array) config('wallet.bonus_tiers', []) as $tier) {
            if ($amount >= (int) $tier['threshold']) {
                return (int) floor($amount * ((float) $tier['percentage'] / 100));
            }
        }

        return 0;
    }

    protected function validateTopupAmount(int $amount): void
    {
        $min = (int) config('wallet.min_topup', self::MIN_TOPUP);
        $max = (int) config('wallet.max_topup_per_transaction', self::MAX_TOPUP);

        if ($amount < $min) {
            throw new \RuntimeException('الحد الأدنى للشحن '.number_format($min).' ل.س');
        }
        if ($amount > $max) {
            throw new \RuntimeException('الحد الأقصى للشحن لكل عملية '.number_format($max).' ل.س');
        }
    }

    protected function checkDailyTopupCap(User $user, int $amount): void
    {
        $cap = (int) config('wallet.daily_topup_cap', self::DAILY_TOPUP_LIMIT);

        $todayTotal = (int) Payment::query()
            ->where('user_id', $user->id)
            ->where('payment_type', 'wallet_topup')
            ->where('status', PaymentStatus::Completed)
            ->whereDate('created_at', now()->toDateString())
            ->sum('amount');

        if (($todayTotal + $amount) > $cap) {
            throw new \RuntimeException('تجاوزت الحد اليومي للشحن (cap: '.number_format($cap).' ل.س)');
        }
    }

    protected function gatewayFactory(): WalletTopupGatewayFactory
    {
        return $this->gatewayFactory ??= app(WalletTopupGatewayFactory::class);
    }

    protected function safeNotify(?User $user, $notification): void
    {
        if (! $user) {
            return;
        }
        try {
            $user->notify($notification);
        } catch (\Throwable $e) {
            Log::channel('payments')->warning('wallet_topup.notify_failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function transfer(User $sender, User $recipient, int $amount): array
    {
        if ($sender->id === $recipient->id) {
            throw new \RuntimeException('لا يمكن التحويل لنفسك');
        }
        if ($amount < self::MIN_TRANSFER) {
            throw new \RuntimeException('الحد الأدنى للتحويل '.number_format(self::MIN_TRANSFER).' ل.س');
        }
        if ($amount > self::MAX_TRANSFER) {
            throw new \RuntimeException('الحد الأقصى للتحويل '.number_format(self::MAX_TRANSFER).' ل.س');
        }

        $senderWallet = Wallet::forUser($sender);

        $dailyTotal = (int) WalletTransaction::query()
            ->where('wallet_id', $senderWallet->id)
            ->where('credit_type', CreditType::TRANSFER_OUT->value)
            ->whereDate('created_at', now()->toDateString())
            ->sum('amount');

        if ($dailyTotal + $amount > self::DAILY_TRANSFER_LIMIT) {
            throw new \RuntimeException('تجاوزت الحد اليومي للتحويلات');
        }

        $fee = (int) round($amount * self::TRANSFER_FEE_PCT);
        $total = $amount + $fee;

        return DB::transaction(function () use ($sender, $recipient, $amount, $fee, $total, $senderWallet) {
            $recipientWallet = Wallet::forUser($recipient);

            $senderWallet->debit(
                $total,
                CreditType::TRANSFER_OUT,
                "تحويل إلى {$recipient->name}",
                $recipient,
                ['fee' => $fee, 'net_amount' => $amount],
            );

            $recipientWallet->credit(
                $amount,
                CreditType::TRANSFER_IN,
                "تحويل من {$sender->name}",
                $sender,
            );

            return [
                'amount' => $amount,
                'fee' => $fee,
                'total' => $total,
                'recipient_id' => $recipient->id,
                'new_balance' => $senderWallet->fresh()->balance,
            ];
        });
    }

    public function requestWithdrawal(User $user, int $amount, string $method, string $accountNumber): WithdrawalRequest
    {
        if ($amount < self::MIN_WITHDRAW) {
            throw new \RuntimeException('الحد الأدنى للسحب '.number_format(self::MIN_WITHDRAW).' ل.س');
        }
        if ($amount > self::MAX_WITHDRAW) {
            throw new \RuntimeException('الحد الأقصى للسحب '.number_format(self::MAX_WITHDRAW).' ل.س');
        }

        $fee = max(self::WITHDRAW_FEE_MIN, (int) round($amount * self::WITHDRAW_FEE_PCT));
        $total = $amount + $fee;

        return DB::transaction(function () use ($user, $amount, $fee, $total, $method, $accountNumber) {
            $wallet = Wallet::forUser($user);

            if ($wallet->available < $total) {
                throw new \RuntimeException('رصيد المحفظة غير كافي');
            }

            $wallet->lock($total);

            return WithdrawalRequest::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'fee' => $fee,
                'total_amount' => $total,
                'withdrawal_method' => $method,
                'account_number' => $accountNumber,
                'status' => 'pending',
            ]);
        });
    }

    public function getExpiringCredits(User $user, int $days = 7): array
    {
        $wallet = Wallet::forUser($user);

        $transactions = $wallet->transactions()
            ->expiring($days)
            ->orderBy('expires_at')
            ->get();

        return [
            'total_expiring' => (int) $transactions->sum('amount'),
            'count' => $transactions->count(),
            'transactions' => $transactions,
        ];
    }

    public function expireCredits(): int
    {
        $expired = WalletTransaction::query()
            ->completed()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->whereNull('processed_at')
            ->get();

        $total = 0;

        foreach ($expired as $tx) {
            DB::transaction(function () use ($tx, &$total) {
                $wallet = $tx->wallet;
                if (! $wallet) {
                    return;
                }

                $deduct = min((int) $tx->amount, (int) $wallet->balance);
                if ($deduct > 0) {
                    $wallet->decrement('balance', $deduct);
                    $wallet->transactions()->create([
                        'type' => 'debit',
                        'credit_type' => 'expired',
                        'amount' => $deduct,
                        'balance_after' => $wallet->fresh()->balance,
                        'reason' => 'admin_adjustment',
                        'reference_type' => WalletTransaction::class,
                        'reference_id' => $tx->id,
                        'description' => 'انتهاء صلاحية رصيد ترويجي',
                        'status' => 'completed',
                        'processed_at' => now(),
                        'created_at' => now(),
                    ]);
                    $total += $deduct;
                }

                $tx->update([
                    'status' => 'expired',
                    'processed_at' => now(),
                ]);
            });
        }

        return $total;
    }

    public function redeemPromoCode(User $user, string $code): array
    {
        $normalized = strtoupper(trim($code));

        $promotion = Promotion::where('code', $normalized)->first();
        if (! $promotion) {
            throw new \RuntimeException('كود الخصم غير صحيح');
        }

        if ($promotion->status !== 'active') {
            throw new \RuntimeException('الكود غير نشط');
        }
        if ($promotion->valid_to && now()->greaterThan($promotion->valid_to)) {
            throw new \RuntimeException('انتهت صلاحية الكود');
        }
        if ($promotion->type !== 'fixed_amount') {
            throw new \RuntimeException('هذا الكود لا يمنح رصيد محفظة');
        }

        $creditAmount = (int) $promotion->value;
        $expiresAt = $promotion->valid_to ? Carbon::parse($promotion->valid_to) : now()->addDays(30);

        $already = PromoUsage::where('user_id', $user->id)
            ->where('promotion_id', $promotion->id)
            ->exists();
        if ($already) {
            throw new \RuntimeException('تم استخدام هذا الكود مسبقاً');
        }

        return DB::transaction(function () use ($user, $promotion, $creditAmount, $expiresAt) {
            $wallet = Wallet::forUser($user);
            $tx = $wallet->credit(
                $creditAmount,
                CreditType::PROMOTIONAL,
                "استخدام كود ترويجي {$promotion->code}",
                $promotion,
                $expiresAt,
                ['promotion_id' => $promotion->id],
            );

            PromoUsage::create([
                'user_id' => $user->id,
                'promotion_id' => $promotion->id,
                'discount_amount' => $creditAmount,
            ]);
            $promotion->increment('current_uses');

            return [
                'amount' => $creditAmount,
                'expires_at' => $expiresAt?->toIso8601String(),
                'new_balance' => $wallet->fresh()->balance,
                'transaction_id' => $tx->id,
            ];
        });
    }
}
