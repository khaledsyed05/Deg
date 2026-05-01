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

class BookingCancelledNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    public function __construct(
        public Booking $booking,
    ) {}

    public function handle(PushNotificationService $push): void
    {
        $user = $this->booking->user;

        if (! $user) {
            return;
        }

        $push->sendNotification($user, 'booking_cancelled', [
            'venue_name' => $this->booking->venue?->getTranslation('name', $user->getLanguage()) ?? '',
            'booking_date' => (string) $this->booking->booking_date,
            'start_time' => (string) $this->booking->start_time,
            'booking_id' => (string) $this->booking->id,
            'booking_code' => (string) $this->booking->booking_code,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('BookingCancelledNotificationJob failed', [
            'booking_id' => $this->booking->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
