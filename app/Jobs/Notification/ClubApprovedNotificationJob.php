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

class ClubApprovedNotificationJob implements ShouldQueue
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
            title: 'تمت الموافقة على ناديك ✅',
            body: "تمت الموافقة على {$clubName} وأصبح متاحاً للحجوزات.",
            data: [
                'type' => 'club_approved',
                'club_id' => (string) $this->club->id,
            ],
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ClubApprovedNotificationJob failed', [
            'club_id' => $this->club->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
