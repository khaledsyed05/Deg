<?php

namespace App\Jobs\Waitlist;

use App\Jobs\Notification\SendFcmNotificationJob;
use App\Repositories\Contracts\VenueWaitlistRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyWaitlistJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    public function __construct(
        public int $venueId,
        public string $bookingDate,
        public string $startTime,
        public int $durationMinutes,
    ) {}

    public function handle(VenueWaitlistRepositoryInterface $waitlistRepo): void
    {
        $entries = $waitlistRepo->findByVenueAndDate(
            $this->venueId,
            $this->bookingDate,
            $this->startTime,
        );

        foreach ($entries as $entry) {
            $user = $entry->user;

            if (! $user || ! $user->fcm_token || ! $user->notifications_push_enabled) {
                continue;
            }

            SendFcmNotificationJob::dispatch(
                $user->fcm_token,
                title: 'الوقت المطلوب أصبح متاحاً!',
                body: "الوقت الذي طلبت إشعارك عنه في {$this->bookingDate} الساعة {$this->startTime} أصبح متاحاً للحجز.",
                data: [
                    'type' => 'waitlist_available',
                    'venue_id' => (string) $this->venueId,
                    'booking_date' => $this->bookingDate,
                    'start_time' => $this->startTime,
                ],
            );

            $entry->update(['notified_at' => now()]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('NotifyWaitlistJob failed', [
            'venue_id' => $this->venueId,
            'booking_date' => $this->bookingDate,
            'start_time' => $this->startTime,
            'error' => $exception->getMessage(),
        ]);
    }
}
