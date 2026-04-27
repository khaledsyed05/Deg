<?php

namespace App\Models;

use App\Enums\CreditType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalRequest extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'fee' => 'integer',
            'total_amount' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $withdrawal): void {
            if (empty($withdrawal->request_code)) {
                $withdrawal->request_code = static::generateRequestCode();
            }
        });
    }

    protected static function generateRequestCode(): string
    {
        $date = now()->format('Ymd');
        $sequence = static::whereDate('created_at', now()->toDateString())->count() + 1;

        return sprintf('WDR-%s-%04d', $date, $sequence);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function approve(User $admin): void
    {
        $this->update([
            'status' => 'approved',
            'processed_by' => $admin->id,
            'processed_at' => now(),
        ]);
    }

    public function complete(User $admin): void
    {
        $wallet = Wallet::forUser($this->user);
        $wallet->unlock($this->total_amount);
        $wallet->debit(
            $this->total_amount,
            CreditType::WITHDRAWAL,
            "سحب رصيد - {$this->request_code}",
            $this,
            ['method' => $this->withdrawal_method, 'account' => $this->account_number, 'fee' => $this->fee]
        );

        $this->update([
            'status' => 'completed',
            'processed_by' => $admin->id,
            'processed_at' => now(),
        ]);
    }

    public function reject(string $reason, User $admin): void
    {
        $wallet = Wallet::forUser($this->user);
        $wallet->unlock($this->total_amount);

        $this->update([
            'status' => 'rejected',
            'admin_notes' => $reason,
            'processed_by' => $admin->id,
            'processed_at' => now(),
        ]);
    }
}
