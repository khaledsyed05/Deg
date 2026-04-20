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

class BookingConfirmedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    public function __construct(
        public Booking $booking,
    ) {}

    public function handle(FcmService $fcm): void
    {
        $user = $this->booking->user;

        if (! $user || ! $user->fcm_token || ! $user->notifications_push_enabled) {
            return;
        }

        $fcm->sendToToken(
            $user->fcm_token,
            title: 'تم تأكيد حجزك ✅',
            body: "تم تأكيد حجزك بتاريخ {$this->booking->booking_date} الساعة {$this->booking->start_time}",
            data: [
                'type' => 'booking_confirmed',
                'booking_id' => (string) $this->booking->id,
                'booking_code' => $this->booking->booking_code,
            ],
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('BookingConfirmedNotificationJob failed', [
            'booking_id' => $this->booking->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
