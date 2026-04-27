<?php

namespace App\Notifications\Football;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MatchResultNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public array $match) {}

    public function via(object $notifiable): array
    {
        return ['database'];
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
}
