<?php

namespace App\Notifications\Football;

use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MatchReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public array $match,
        public string $reminderType,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        $home = $this->match['homeTeam']['name'] ?? '';
        $away = $this->match['awayTeam']['name'] ?? '';
        $when = $this->reminderType === '1h_before' ? 'بعد ساعة' : 'بعد 15 دقيقة';

        return [
            'type' => 'football_match_reminder',
            'title' => "🏟️ تذكير بمباراة {$when}",
            'body' => "{$home} ضد {$away}",
            'reminder_type' => $this->reminderType,
            'match_external_id' => (string) ($this->match['id'] ?? ''),
            'match' => $this->match,
        ];
    }

    /**
     * @return array{type: string, data: array<string, mixed>}
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'type' => 'match_reminder',
            'data' => [
                'home_team' => $this->match['homeTeam']['name'] ?? '',
                'away_team' => $this->match['awayTeam']['name'] ?? '',
                'reminder_type' => $this->reminderType,
                'match_external_id' => (string) ($this->match['id'] ?? ''),
            ],
        ];
    }
}
