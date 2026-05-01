<?php

namespace App\Notifications;

use App\Models\Achievement;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AchievementUnlockedNotification extends Notification
{
    use Queueable;

    public function __construct(public Achievement $achievement) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $metadata = $this->achievement->getMetadata();

        return [
            'type' => 'achievement_unlocked',
            'title' => 'إنجاز جديد! 🏆',
            'body' => 'حصلت على إنجاز: '.$metadata['title'],
            'achievement' => [
                'type' => $this->achievement->type->value,
                'title' => $metadata['title'],
                'description' => $metadata['description'],
                'icon' => $metadata['icon'],
                'points' => $metadata['points'],
            ],
        ];
    }

    /**
     * @return array{type: string, data: array<string, mixed>}
     */
    public function toFcm(object $notifiable): array
    {
        $metadata = $this->achievement->getMetadata();

        return [
            'type' => 'achievement_unlocked',
            'data' => [
                'achievement_title' => (string) ($metadata['title'] ?? ''),
                'achievement_type' => $this->achievement->type->value,
                'points' => (string) ($metadata['points'] ?? 0),
            ],
        ];
    }
}
