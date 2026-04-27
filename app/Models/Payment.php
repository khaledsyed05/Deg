<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\DepositStatus;
use App\Enums\PaymentFlowType;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $hidden = ['provider_meta'];

    protected function casts(): array
    {
        return [
            'provider' => PaymentProvider::class,
            'flow_type' => PaymentFlowType::class,
            'status' => PaymentStatus::class,
            'provider_meta' => 'encrypted:array',
            'provider_payload' => 'array',
            'initiated_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function refundedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::Completed);
    }

    public function scopeWalletTopups(Builder $query): Builder
    {
        return $query->where('payment_type', 'wallet_topup');
    }

    public function isWalletTopup(): bool
    {
        return $this->payment_type === 'wallet_topup';
    }

    /**
     * Generate a human-readable payment reference in the form PAY-{PROV}-{BOOKING}-{SEQ}.
     */
    public static function generateReference(PaymentProvider|string $provider, int $bookingId): string
    {
        $prov = $provider instanceof PaymentProvider ? $provider->value : $provider;
        $code = strtoupper(substr(str_replace('_', '', $prov), 0, 3));
        $seq = str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);

        return "PAY-{$code}-{$bookingId}-{$seq}";
    }

    /**
     * Mark the payment completed, record provider metadata, and flip the booking deposit state.
     */
    public function markCompleted(array $providerData = []): void
    {
        $this->update([
            'status' => PaymentStatus::Completed,
            'completed_at' => now(),
            'provider_transaction_id' => $providerData['transaction_id'] ?? $this->provider_transaction_id,
            'provider_reference' => $providerData['reference'] ?? $this->provider_reference,
            'provider_meta' => array_merge($this->provider_meta ?? [], $providerData),
        ]);

        if ($this->booking) {
            $this->booking->update([
                'status' => BookingStatus::Confirmed,
                'deposit_status' => DepositStatus::Paid,
            ]);

            activity('payment')
                ->causedBy($this->user)
                ->performedOn($this->booking)
                ->event('payment_completed')
                ->withProperties(['payment_id' => $this->id, 'amount' => (int) $this->amount])
                ->log('Payment completed');
        }
    }

    public function markFailed(?string $reason = null): void
    {
        $this->update([
            'status' => PaymentStatus::Failed,
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);
    }
}
