<?php

namespace App\Jobs\Notification;

use App\Models\Booking;
use App\Services\Notification\FcmService;
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

    public function handle(FcmService $fcm): void
    {
        // Skip if booking is no longer active
        if (! in_array($this->booking->status->value, ['confirmed', 'scheduled'])) {
            return;
        }

        $user = $this->booking->user;

        if (! $user || ! $user->fcm_token || ! $user->notifications_reminders_enabled) {
            return;
        }

        $fcm->sendToToken(
            $user->fcm_token,
            title: "تذكير: حجزك بعد {$this->hoursBeforeStart} ساعة",
            body: "حجزك في {$this->booking->start_time} اليوم",
            data: [
                'type' => 'booking_reminder',
                'booking_id' => (string) $this->booking->id,
                'hours_before' => (string) $this->hoursBeforeStart,
            ],
        );
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
