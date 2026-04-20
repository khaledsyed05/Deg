<?php

namespace App\Jobs\Notification;

use App\Services\Notification\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Generic FCM push notification job.
 * Use domain-specific jobs (BookingConfirmedNotificationJob, etc.) when possible.
 */
class SendFcmNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    public function __construct(
        public string $fcmToken,
        public string $title,
        public string $body,
        public array $data = [],
    ) {}

    public function handle(FcmService $fcm): void
    {
        $fcm->sendToToken($this->fcmToken, $this->title, $this->body, $this->data);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendFcmNotificationJob failed', [
            'token' => substr($this->fcmToken, 0, 20).'...',
            'title' => $this->title,
            'error' => $exception->getMessage(),
        ]);
    }
}
