<?php

namespace App\Models;

use App\Enums\RecurrenceFrequency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'next_booking_date' => 'date',
            'next_charge_date' => 'date',
            'auto_pay' => 'boolean',
            'paused_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'frequency' => RecurrenceFrequency::class,
            'discount_percentage' => 'decimal:2',
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

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function instances(): HasMany
    {
        return $this->hasMany(BookingInstance::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeDueForProcessing(Builder $query): Builder
    {
        return $query->active()
            ->whereNotNull('next_booking_date')
            ->where('next_booking_date', '<=', now()->addDays(7)->toDateString());
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function canBePaused(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->pause_count >= 3) {
            return false;
        }

        return ! $this->instances()->where('payment_status', 'pending')->exists();
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function pause(): array
    {
        if (! $this->canBePaused()) {
            return ['success' => false, 'message' => 'لا يمكن إيقاف الاشتراك مؤقتاً'];
        }

        $this->update(['status' => 'paused', 'paused_at' => now()]);
        $this->increment('pause_count');

        return ['success' => true, 'message' => 'تم إيقاف الاشتراك مؤقتاً'];
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function resume(): array
    {
        if ($this->status !== 'paused') {
            return ['success' => false, 'message' => 'الاشتراك غير موقف'];
        }

        if ($this->paused_at && $this->paused_at->copy()->addDays(60)->isPast()) {
            $this->cancel('تجاوز مدة الإيقاف المؤقت');

            return ['success' => false, 'message' => 'تم إلغاء الاشتراك لتجاوز مدة الإيقاف'];
        }

        $this->update(['status' => 'active', 'paused_at' => null]);
        $this->calculateNextBookingDate();

        return ['success' => true, 'message' => 'تم استئناف الاشتراك'];
    }

    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        $this->instances()
            ->where('status', 'scheduled')
            ->update(['status' => 'skipped', 'skipped_reason' => 'subscription_cancelled']);

        try {
            activity()
                ->causedBy($this->user)
                ->performedOn($this)
                ->event('subscription_cancelled')
                ->withProperties(['reason' => $reason])
                ->log('User cancelled subscription');
        } catch (\Throwable $e) {
            // Activity log is optional context; swallow.
        }
    }

    public function calculateNextBookingDate(): void
    {
        $current = $this->next_booking_date
            ? $this->next_booking_date->copy()
            : $this->start_date->copy();

        $next = match ($this->frequency) {
            RecurrenceFrequency::Weekly => $current->addWeek(),
            RecurrenceFrequency::Biweekly => $current->addWeeks(2),
            RecurrenceFrequency::Monthly => $current->addMonth(),
            RecurrenceFrequency::Custom => $current->addDays((int) ($this->interval ?? 1)),
        };

        if ($this->end_date && $next->gt($this->end_date)) {
            $this->update(['status' => 'expired']);

            return;
        }

        $this->update([
            'next_booking_date' => $next->toDateString(),
            'next_charge_date' => $next->copy()->subDays(3)->toDateString(),
        ]);
    }

    public function getSavingsAmount(): int
    {
        $regularHourly = (int) ($this->venue?->price_from ?? 0);
        $regular = $regularHourly * (int) $this->duration_hours;

        return max(0, $regular - (int) $this->price_per_booking);
    }
}
