<?php

namespace App\Notifications\Channels;

use App\Models\User;
use App\Services\Notification\PushNotificationService;
use Illuminate\Notifications\Notification;

/**
 * Bridges Laravel notifications to PushNotificationService.
 *
 * The notification class must implement toFcm($notifiable) returning
 * ['type' => string, 'data' => array<string, mixed>].
 */
class FcmChannel
{
    public function __construct(
        private readonly PushNotificationService $push,
    ) {}

    public function send(object $notifiable, Notification $notification): bool
    {
        if (! $notifiable instanceof User || ! method_exists($notification, 'toFcm')) {
            return false;
        }

        $payload = $notification->toFcm($notifiable);

        $type = (string) ($payload['type'] ?? '');
        $data = (array) ($payload['data'] ?? []);

        if ($type === '') {
            return false;
        }

        return $this->push->sendNotification($notifiable, $type, $data);
    }
}
