<?php

namespace App\Jobs\Notification;

use App\Models\Club;
use App\Services\Notification\PushNotificationService;
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

    public function handle(PushNotificationService $push): void
    {
        $owner = $this->club->owner;

        if (! $owner) {
            return;
        }

        $push->sendNotification($owner, 'club_approved', [
            'club_name' => (string) $this->club->getTranslation('name', $owner->getLanguage()),
            'club_id' => (string) $this->club->id,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ClubApprovedNotificationJob failed', [
            'club_id' => $this->club->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
