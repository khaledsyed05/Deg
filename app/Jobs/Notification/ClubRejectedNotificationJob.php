<?php

namespace App\Jobs\Notification;

use App\Models\Club;
use App\Services\Notification\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ClubRejectedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    public function __construct(
        public Club $club,
    ) {}

    public function handle(FcmService $fcm): void
    {
        $owner = $this->club->owner;

        if (! $owner || ! $owner->fcm_token || ! $owner->notifications_push_enabled) {
            return;
        }

        $clubName = $this->club->getTranslation('name', 'ar');

        $fcm->sendToToken(
            $owner->fcm_token,
            title: 'تم رفض طلب نادي',
            body: "تم رفض طلب {$clubName}. السبب: {$this->club->rejection_reason}",
            data: [
                'type' => 'club_rejected',
                'club_id' => (string) $this->club->id,
            ],
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ClubRejectedNotificationJob failed', [
            'club_id' => $this->club->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
