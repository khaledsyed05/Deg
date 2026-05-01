<?php

namespace App\Jobs\Notification;

use App\Models\Booking;
use App\Services\Notification\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BookingReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public array $backoff = [60, 180];

    public function __construct(
        public Booking $booking,
        public int $hoursBeforeStart,
    ) {}

    public function handle(PushNotificationService $push): void
    {
        if (! in_array($this->booking->status->value, ['confirmed', 'scheduled'])) {
            return;
        }

        $user = $this->booking->user;

        if (! $user) {
            return;
        }

        $push->sendNotification($user, 'booking_reminder', [
            'venue_name' => $this->booking->venue?->getTranslation('name', $user->getLanguage()) ?? '',
            'booking_date' => (string) $this->booking->booking_date,
            'start_time' => (string) $this->booking->start_time,
            'booking_id' => (string) $this->booking->id,
            'hours_before' => (string) $this->hoursBeforeStart,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('BookingReminderJob failed', [
            'booking_id' => $this->booking->id,
            'hours_before' => $this->hoursBeforeStart,
            'error' => $exception->getMessage(),
        ]);
    }
}
