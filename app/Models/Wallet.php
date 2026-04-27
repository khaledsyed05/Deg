<?php

namespace App\Models;

use App\Enums\CreditType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Wallet extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'balance' => 'integer',
            'locked' => 'integer',
            'total_earned' => 'integer',
            'total_spent' => 'integer',
            'total_topup' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function getAvailableAttribute(): int
    {
        return max(0, (int) $this->balance - (int) $this->locked);
    }

    public static function forUser(User $user): self
    {
        return self::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'currency' => 'SYP']
        );
    }

    /**
     * Credit the wallet (additive).
     */
    public function credit(
        int $amount,
        CreditType $creditType,
        string $description,
        ?Model $reference = null,
        ?\DateTimeInterface $expiresAt = null,
        ?array $metadata = null
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Credit amount must be positive.');
        }

        return DB::transaction(function () use ($amount, $creditType, $description, $reference, $expiresAt, $metadata) {
            $this->refresh();
            $this->increment('balance', $amount);
            $this->increment('total_earned', $amount);
            if ($creditType === CreditType::TOPUP) {
                $this->increment('total_topup', $amount);
            }

            return WalletTransaction::create([
                'wallet_id' => $this->id,
                'type' => 'credit',
                'credit_type' => $creditType->value,
                'amount' => $amount,
                'balance_after' => $this->balance,
                'reason' => $this->mapReason($creditType),
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference?->getKey(),
                'description' => $description,
                'metadata' => $metadata,
                'expires_at' => $expiresAt,
                'status' => 'completed',
                'processed_at' => now(),
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Debit the wallet (subtract). Checks available balance (balance - locked).
     */
    public function debit(
        int $amount,
        CreditType $creditType,
        string $description,
        ?Model $reference = null,
        ?array $metadata = null
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Debit amount must be positive.');
        }

        return DB::transaction(function () use ($amount, $creditType, $description, $reference, $metadata) {
            $this->refresh();

            if ($this->available < $amount) {
                throw new \RuntimeException('رصيد المحفظة غير كافي');
            }

            $this->decrement('balance', $amount);
            $this->increment('total_spent', $amount);

            return WalletTransaction::create([
                'wallet_id' => $this->id,
                'type' => 'debit',
                'credit_type' => $creditType->value,
                'amount' => $amount,
                'balance_after' => $this->balance,
                'reason' => $this->mapReason($creditType),
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference?->getKey(),
                'description' => $description,
                'metadata' => $metadata,
                'status' => 'completed',
                'processed_at' => now(),
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Reserve (lock) funds for a pending booking or withdrawal.
     */
    public function lock(int $amount): void
    {
        DB::transaction(function () use ($amount) {
            $this->refresh();
            if ($this->available < $amount) {
                throw new \RuntimeException('رصيد المحفظة غير كافي');
            }
            $this->increment('locked', $amount);
        });
    }

    public function unlock(int $amount): void
    {
        DB::transaction(function () use ($amount) {
            $this->refresh();
            $this->decrement('locked', min($amount, (int) $this->locked));
        });
    }

    /**
     * Map new credit_type to legacy reason enum (for backward compatibility).
     */
    protected function mapReason(CreditType $type): string
    {
        return match ($type) {
            CreditType::BOOKING => 'booking_payment',
            CreditType::REFUND => 'booking_refund',
            default => 'admin_adjustment',
        };
    }
}
