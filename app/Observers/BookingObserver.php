<?php

namespace App\Observers;

use App\Enums\BookingStatus;
use App\Jobs\Notification\BookingCancelledNotificationJob;
use App\Jobs\Notification\BookingConfirmedNotificationJob;
use App\Jobs\Notification\BookingReminderJob;
use App\Jobs\Waitlist\NotifyWaitlistJob;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BookingObserver
{
    public function created(Booking $booking): void
    {
        if ($booking->status !== BookingStatus::Confirmed) {
            return;
        }

        try {
            BookingConfirmedNotificationJob::dispatch($booking);

            $startsAt = Carbon::parse($booking->booking_date->format('Y-m-d').' '.$booking->start_time);

            if ($startsAt->diffInHours(now()) >= 2) {
                BookingReminderJob::dispatch($booking, hoursBeforeStart: 2)
                    ->delay($startsAt->copy()->subHours(2));
            }

            if ($startsAt->diffInHours(now()) >= 1) {
                BookingReminderJob::dispatch($booking, hoursBeforeStart: 1)
                    ->delay($startsAt->copy()->subHour());
            }
        } catch (\Throwable $e) {
            Log::error('BookingObserver: failed to dispatch confirmation jobs', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function updated(Booking $booking): void
    {
        if (! $booking->isDirty('status')) {
            return;
        }

        if ($booking->status !== BookingStatus::Cancelled) {
            return;
        }

        try {
            BookingCancelledNotificationJob::dispatch($booking);

            NotifyWaitlistJob::dispatch(
                $booking->venue_id,
                $booking->booking_date,
                $booking->start_time,
                $booking->duration_minutes,
            );
        } catch (\Throwable $e) {
            Log::error('BookingObserver: failed to dispatch cancellation jobs', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
