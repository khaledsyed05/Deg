<?php

namespace App\Notifications\Football;

use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MatchResultNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public array $match) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        $home = $this->match['home_team']['name'] ?? '';
        $away = $this->match['away_team']['name'] ?? '';
        $score = ($this->match['score']['home'] ?? 0).' - '.($this->match['score']['away'] ?? 0);

        return [
            'type' => 'football_match_result',
            'title' => '🏁 نتيجة المباراة',
            'body' => "{$home} {$score} {$away}",
            'fixture_id' => (string) ($this->match['fixture_id'] ?? ''),
            'match' => $this->match,
        ];
    }

    /**
     * @return array{type: string, data: array<string, mixed>}
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'type' => 'match_result',
            'data' => [
                'home_team' => $this->match['home_team']['name'] ?? '',
                'away_team' => $this->match['away_team']['name'] ?? '',
                'score' => ($this->match['score']['home'] ?? 0).' - '.($this->match['score']['away'] ?? 0),
                'fixture_id' => (string) ($this->match['fixture_id'] ?? ''),
            ],
        ];
    }
}
