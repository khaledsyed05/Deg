<?php

namespace App\Jobs\Notification;

use App\Models\Team;
use App\Models\User;
use App\Services\Notification\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TeamInvitationNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    public function __construct(
        public Team $team,
        public int $invitedUserId,
        public int $inviterUserId,
    ) {}

    public function handle(PushNotificationService $push): void
    {
        $invited = User::find($this->invitedUserId);
        $inviter = User::find($this->inviterUserId);

        if (! $invited) {
            return;
        }

        $push->sendNotification($invited, 'team_invitation', [
            'inviter_name' => (string) ($inviter?->name ?? ''),
            'team' => (string) $this->team->name,
            'team_id' => (string) $this->team->id,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('TeamInvitationNotificationJob failed', [
            'team_id' => $this->team->id,
            'invited_user_id' => $this->invitedUserId,
            'error' => $exception->getMessage(),
        ]);
    }
}
