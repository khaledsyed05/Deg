<?php

namespace App\Services\Wallet;

use App\Enums\BookingStatus;
use App\Enums\CreditType;
use App\Exceptions\Wallet\BookingNotPayableException;
use App\Exceptions\Wallet\InsufficientBalanceException;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Support\Idempotency;
use Illuminate\Support\Facades\DB;

/**
 * Pay a pending booking from the user's wallet.
 *
 * Atomicity: a single DB::transaction with `lockForUpdate()` on the wallet
 * row guarantees concurrent pay-booking calls for the same wallet either
 * serialize or fail.
 *
 * Idempotency: the call is wrapped in `Idempotency::run($idempotencyKey,
 * $callback)` so duplicate POSTs return the original transaction without
 * a second debit.
 */
class PayBookingService
{
    public function execute(
        User $user,
        Booking $booking,
        string $idempotencyKey,
        int $amount,
    ): WalletTransaction {
        return Idempotency::run($idempotencyKey, function () use ($user, $booking, $idempotencyKey, $amount) {
            return DB::transaction(function () use ($user, $booking, $idempotencyKey, $amount) {
                $wallet = Wallet::query()
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first()
                    ?? Wallet::forUser($user);

                $booking = $booking->fresh();

                $this->guardBookingState($booking, $amount);

                if ($wallet->available < $amount) {
                    throw new InsufficientBalanceException(
                        __('wallet.insufficient_balance'),
                        available: (int) $wallet->available,
                        required: $amount,
                    );
                }

                $wallet->decrement('balance', $amount);
                $wallet->increment('total_spent', $amount);
                $wallet->refresh();

                $transaction = WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'debit',
                    'credit_type' => CreditType::BOOKING->value,
                    'idempotency_key' => $idempotencyKey,
                    'amount' => $amount,
                    'balance_after' => (int) $wallet->balance,
                    'reason' => 'booking_payment',
                    'reference_type' => Booking::class,
                    'reference_id' => $booking->id,
                    'description' => "Booking #{$booking->booking_code}",
                    'status' => 'completed',
                    'processed_at' => now(),
                    'created_at' => now(),
                    'created_by' => $user->id,
                ]);

                $booking->update([
                    'status' => BookingStatus::Confirmed,
                    'wallet_transaction_id' => $transaction->id,
                ]);

                activity()
                    ->causedBy($user)
                    ->performedOn($booking)
                    ->withProperties([
                        'wallet_transaction_id' => $transaction->id,
                        'amount' => $amount,
                        'idempotency_key' => $idempotencyKey,
                    ])
                    ->event('wallet.pay_booking')
                    ->log('Booking paid from wallet');

                AuditLog::record(
                    action: 'wallet.pay_booking',
                    userId: $user->id,
                    subject: $booking,
                    changes: [
                        'wallet_transaction_id' => $transaction->id,
                        'amount' => $amount,
                        'idempotency_key' => $idempotencyKey,
                    ],
                    ip: request()?->ip(),
                );

                return $transaction;
            });
        });
    }

    private function guardBookingState(Booking $booking, int $amount): void
    {
        if ($booking->status !== BookingStatus::PendingPayment) {
            throw new BookingNotPayableException(__('wallet.booking_not_payable'));
        }

        if ((int) $booking->total_price !== $amount) {
            throw new BookingNotPayableException(__('wallet.amount_mismatch'));
        }
    }
}
