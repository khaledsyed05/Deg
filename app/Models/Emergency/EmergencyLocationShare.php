<?php

namespace App\Models\Emergency;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyLocationShare extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'recipient_phones' => 'array',
            'last_latitude' => 'float',
            'last_longitude' => 'float',
            'last_updated_at' => 'datetime',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'stopped_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $share): void {
            if (empty($share->share_token)) {
                $share->share_token = bin2hex(random_bytes(32));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
