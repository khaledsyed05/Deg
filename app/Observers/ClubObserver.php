<?php

namespace App\Observers;

use App\Enums\ClubStatus;
use App\Jobs\Notification\ClubApprovedNotificationJob;
use App\Jobs\Notification\ClubRejectedNotificationJob;
use App\Models\Club;
use Illuminate\Support\Facades\Log;

class ClubObserver
{
    public function updated(Club $club): void
    {
        if (! $club->isDirty('status')) {
            return;
        }

        if ($club->status === ClubStatus::Active) {
            try {
                ClubApprovedNotificationJob::dispatch($club);
            } catch (\Throwable $e) {
                Log::error('ClubObserver: failed to dispatch approval notification', [
                    'club_id' => $club->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($club->status === ClubStatus::Rejected) {
            try {
                ClubRejectedNotificationJob::dispatch($club);
            } catch (\Throwable $e) {
                Log::error('ClubObserver: failed to dispatch rejection notification', [
                    'club_id' => $club->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
