<?php

namespace App\Models;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\CommissionType;
use App\Enums\DepositStatus;
use App\Enums\ManualType;
use App\Enums\RemainingStatus;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory, LogsActivity;

    protected $guarded = [];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('booking')
            ->logOnly(['venue_id', 'booking_date', 'start_time', 'end_time', 'total_price', 'status'])
            ->logOnlyDirty();
    }

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'reminder_2h_sent_at' => 'datetime',
            'reminder_1h_sent_at' => 'datetime',
            'remaining_confirmed_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'status' => BookingStatus::class,
            'source' => BookingSource::class,
            'manual_type' => ManualType::class,
            'commission_type' => CommissionType::class,
            'deposit_status' => DepositStatus::class,
            'remaining_status' => RemainingStatus::class,
            'recurrence_pattern' => 'array',
            'is_recurring' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function remainingConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'remaining_confirmed_by');
    }

    public function recurrenceParent(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'recurrence_parent_id');
    }

    public function recurrenceChildren(): HasMany
    {
        return $this->hasMany(Booking::class, 'recurrence_parent_id');
    }
}
