<?php

namespace App\Notifications\Football;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class GoalScoredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public array $match,
        public string $scoringSide,
        public int $goalsScored,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $scoringTeam = $this->match[$this->scoringSide.'_team']['name'] ?? '';
        $score = ($this->match['score']['home'] ?? 0).' - '.($this->match['score']['away'] ?? 0);
        $home = $this->match['home_team']['name'] ?? '';
        $away = $this->match['away_team']['name'] ?? '';
        $minute = $this->match['minute'] ?? '';

        return [
            'type' => 'football_goal',
            'title' => "⚽ هدف! {$scoringTeam}",
            'body' => "{$home} {$score} {$away} ({$minute}')",
            'fixture_id' => (string) ($this->match['fixture_id'] ?? ''),
            'scoring_side' => $this->scoringSide,
            'goals_scored' => $this->goalsScored,
            'match' => $this->match,
        ];
    }
}
