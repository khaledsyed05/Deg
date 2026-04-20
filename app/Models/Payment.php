<?php

namespace App\Models;

use App\Enums\PaymentFlowType;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
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
}
