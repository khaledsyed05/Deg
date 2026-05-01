<?php

namespace App\Jobs\Notification;

use App\Models\Booking;
use App\Models\User;
use App\Services\Notification\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GroupBookingInviteNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    public function __construct(
        public Booking $booking,
        public int $invitedUserId,
    ) {}

    public function handle(PushNotificationService $push): void
    {
        $invited = User::find($this->invitedUserId);
        $captain = $this->booking->captain ?? $this->booking->user;

        if (! $invited) {
            return;
        }

        $push->sendNotification($invited, 'group_booking_invite', [
            'inviter_name' => (string) ($captain?->name ?? ''),
            'venue_name' => (string) ($this->booking->venue?->getTranslation('name', $invited->getLanguage()) ?? ''),
            'booking_id' => (string) $this->booking->id,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GroupBookingInviteNotificationJob failed', [
            'booking_id' => $this->booking->id,
            'invited_user_id' => $this->invitedUserId,
            'error' => $exception->getMessage(),
        ]);
    }
}
