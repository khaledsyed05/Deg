<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingInstance extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'last_payment_attempt_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function skip(string $reason): void
    {
        $this->update(['status' => 'skipped', 'skipped_reason' => $reason]);
    }

    public function createBooking(): ?Booking
    {
        $subscription = $this->subscription;

        if (! $subscription) {
            return null;
        }

        try {
            return DB::transaction(function () use ($subscription) {
                $date = $this->scheduled_date->toDateString();
                $startTime = substr((string) $this->scheduled_start_time, 0, 5);
                $endTime = substr((string) $this->scheduled_end_time, 0, 5);

                $booking = Booking::create([
                    'user_id' => $subscription->user_id,
                    'subscription_id' => $subscription->id,
                    'venue_id' => $subscription->venue_id,
                    'booking_code' => Booking::generateBookingCode(),
                    'source' => 'mobile',
                    'status' => 'confirmed',
                    'booking_date' => $date,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'starts_at' => Carbon::parse($date.' '.$startTime),
                    'ends_at' => Carbon::parse($date.' '.$endTime),
                    'duration_minutes' => ((int) $subscription->duration_hours) * 60,
                    'venue_price' => (int) $subscription->price_per_booking,
                    'total_price' => (int) $subscription->price_per_booking,
                    'currency' => 'SYP',
                    'is_recurring' => 1,
                    'deposit_amount' => (int) round($subscription->price_per_booking * 0.30),
                    'deposit_status' => 'paid',
                    'remaining_amount' => (int) $subscription->price_per_booking - (int) round($subscription->price_per_booking * 0.30),
                    'remaining_status' => 'due_on_arrival',
                ]);

                $this->update([
                    'booking_id' => $booking->id,
                    'status' => 'created',
                    'payment_status' => 'completed',
                ]);

                return $booking;
            });
        } catch (\Throwable $e) {
            Log::error('BookingInstance::createBooking failed', [
                'instance_id' => $this->id,
                'error' => $e->getMessage(),
            ]);

            $this->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
