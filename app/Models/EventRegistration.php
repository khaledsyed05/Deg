<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRegistration extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'participant_info' => 'array',
            'amount_paid' => 'decimal:2',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'registered_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $reg): void {
            if (empty($reg->registration_number)) {
                $date = now()->format('Ymd');
                $count = self::whereDate('created_at', today())->count() + 1;
                $reg->registration_number = sprintf('REG-%s-%04d', $date, $count);
            }
            if (empty($reg->registered_at)) {
                $reg->registered_at = now();
            }
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function refundRequest(): BelongsTo
    {
        return $this->belongsTo(RefundRequest::class);
    }
}
