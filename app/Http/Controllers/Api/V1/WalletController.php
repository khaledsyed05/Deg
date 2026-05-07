<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Wallet\PayBookingRequest;
use App\Http\Requests\Api\V1\Wallet\ResendTopupOtpRequest;
use App\Http\Requests\Api\V1\Wallet\UpdateWalletSettingsRequest;
use App\Http\Requests\Api\V1\Wallet\VerifyTopupRequest;
use App\Http\Requests\Api\V1\Wallet\WalletTopupRequest;
use App\Http\Resources\Wallet\WalletAccountResource;
use App\Http\Resources\Wallet\WalletTransactionResource;
use App\Http\Traits\ApiResponse;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Wallet\PayBookingService;
use App\Services\Wallet\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected WalletService $walletService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return $this->account($request);
    }

    public function account(Request $request): JsonResponse
    {
        $wallet = Wallet::forUser($request->user());

        return $this->success(new WalletAccountResource($wallet));
    }

    public function topup(WalletTopupRequest $request): JsonResponse
    {
        try {
            $result = $this->walletService->initiateTopup(
                $request->user(),
                (int) $request->validated('amount'),
                $request->validated('method'),
                $request->validated('phone'),
            );

            return $this->success($result, $this->initiateMessage($result['method']));
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }

    public function verifyTopup(VerifyTopupRequest $request): JsonResponse
    {
        try {
            $result = $this->walletService->confirmTopup(
                (int) $request->validated('payment_id'),
                $request->validated('otp'),
                $request->user(),
            );

            return $this->success($result, 'تم شحن المحفظة بنجاح');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }

    public function resendTopupOtp(ResendTopupOtpRequest $request): JsonResponse
    {
        try {
            $sent = $this->walletService->resendTopupOtp(
                (int) $request->validated('payment_id'),
                $request->user(),
            );

            return $sent
                ? $this->success(null, 'تم إعادة إرسال رمز التحقق')
                : $this->error('تعذّر إعادة إرسال رمز التحقق', null, 422);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }

    public function topupStatus(int $paymentId, Request $request): JsonResponse
    {
        $payment = Payment::query()
            ->where('id', $paymentId)
            ->where('user_id', $request->user()->id)
            ->where('payment_type', 'wallet_topup')
            ->firstOrFail();

        return $this->success([
            'payment_id' => $payment->id,
            'status' => $payment->status->value,
            'amount' => (int) $payment->amount,
            'bonus' => (int) $payment->bonus_amount,
            'total_credited' => (int) ($payment->total_credited ?? ($payment->amount + $payment->bonus_amount)),
            'method' => $payment->provider->value,
            'created_at' => $payment->created_at?->toIso8601String(),
            'completed_at' => $payment->completed_at?->toIso8601String(),
        ]);
    }

    public function cancelTopup(int $paymentId, Request $request): JsonResponse
    {
        try {
            $payment = $this->walletService->cancelTopup($paymentId, $request->user());

            return $this->success(['payment_id' => $payment->id, 'status' => $payment->status->value], 'تم إلغاء عملية الشحن');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }

    private function initiateMessage(string $method): string
    {
        return match ($method) {
            'syriatel' => 'تم إرسال رمز التحقق إلى رقم Syriatel Cash الخاص بك',
            'mtn' => 'تم إرسال رمز التحقق إلى رقم MTN Cash الخاص بك',
            'bank' => 'سيتم تحويلك إلى صفحة الدفع البنكي',
            default => 'تم بدء عملية الشحن',
        };
    }

    /**
     * Wallet settings (auto-topup configuration + low-balance alert
     * preference). Persisted on wallets.settings (json) — defaults to
     * the disabled / null shape on first read for a fresh user.
     */
    public function getSettings(Request $request): JsonResponse
    {
        $wallet = Wallet::forUser($request->user());

        return $this->success($this->settingsPayload($wallet));
    }

    public function updateSettings(UpdateWalletSettingsRequest $request): JsonResponse
    {
        $wallet = Wallet::forUser($request->user());
        $current = is_array($wallet->settings) ? $wallet->settings : [];

        $merged = array_replace($current, $request->validated());

        // When auto_topup is explicitly disabled, clear the dependent fields
        // so the next GET reflects the cleared state.
        if (array_key_exists('auto_topup_enabled', $merged) && $merged['auto_topup_enabled'] === false) {
            $merged['auto_topup_threshold'] = null;
            $merged['auto_topup_amount'] = null;
            $merged['auto_topup_payment_method'] = null;
        }

        $wallet->settings = $merged;
        $wallet->save();

        AuditLog::record(
            action: 'wallet.settings_changed',
            userId: $request->user()->id,
            subject: $wallet,
            changes: ['before' => $current, 'after' => $merged],
            ip: $request->ip(),
        );

        return $this->success($this->settingsPayload($wallet->fresh()));
    }

    /**
     * @return array<string, mixed>
     */
    private function settingsPayload(Wallet $wallet): array
    {
        $settings = is_array($wallet->settings) ? $wallet->settings : [];

        return [
            'auto_topup_enabled' => (bool) ($settings['auto_topup_enabled'] ?? false),
            'auto_topup_threshold' => isset($settings['auto_topup_threshold'])
                ? (int) $settings['auto_topup_threshold']
                : null,
            'auto_topup_amount' => isset($settings['auto_topup_amount'])
                ? (int) $settings['auto_topup_amount']
                : null,
            'auto_topup_payment_method' => $settings['auto_topup_payment_method'] ?? null,
            'low_balance_alert' => (bool) ($settings['low_balance_alert'] ?? false),
        ];
    }

    public function transactions(Request $request): JsonResponse
    {
        $type = $request->query('type');
        $wallet = Wallet::forUser($request->user());

        $transactions = $wallet->transactions()
            ->when($type, fn ($q) => $q->where('credit_type', $type))
            ->orderByDesc('created_at')
            ->paginate(20);

        return $this->paginated($transactions, WalletTransactionResource::class);
    }

    public function payBooking(PayBookingRequest $request, PayBookingService $service): JsonResponse
    {
        $user = $request->user();
        $booking = Booking::findOrFail((int) $request->validated('booking_id'));

        if ($booking->user_id !== $user->id) {
            return $this->forbidden(__('wallet.booking_not_owned'));
        }

        $transaction = $service->execute(
            user: $user,
            booking: $booking,
            idempotencyKey: (string) $request->validated('idempotency_key'),
            amount: (int) $request->validated('amount'),
        );

        return $this->success(
            new WalletTransactionResource($transaction),
            __('wallet.payment_successful'),
        );
    }

    public function transfer(Request $request): JsonResponse
    {
        $data = $request->validate([
            'recipient_id' => 'required|integer|exists:users,id',
            'amount' => 'required|integer|min:'.WalletService::MIN_TRANSFER.'|max:'.WalletService::MAX_TRANSFER,
        ]);

        $recipient = User::findOrFail($data['recipient_id']);

        try {
            $result = $this->walletService->transfer($request->user(), $recipient, (int) $data['amount']);

            return $this->success($result, 'تم التحويل بنجاح');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }

    public function redeem(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:50',
        ]);

        try {
            $result = $this->walletService->redeemPromoCode($request->user(), $data['code']);

            return $this->success($result, 'تم استخدام الكود بنجاح');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }

    public function expiring(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 7);
        $result = $this->walletService->getExpiringCredits($request->user(), max(1, min(90, $days)));

        return $this->success($result);
    }

    public function withdraw(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => 'required|integer|min:'.WalletService::MIN_WITHDRAW.'|max:'.WalletService::MAX_WITHDRAW,
            'method' => 'required|in:syriatel,mtn,bank',
            'account_number' => 'required|string|max:255',
        ]);

        try {
            $withdrawal = $this->walletService->requestWithdrawal(
                $request->user(),
                (int) $data['amount'],
                $data['method'],
                $data['account_number'],
            );

            return $this->success($withdrawal, 'تم إنشاء طلب السحب', 201);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }
}
