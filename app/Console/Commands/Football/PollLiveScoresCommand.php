<?php

namespace App\Console\Commands\Football;

use App\Models\Football\LiveMatchState;
use App\Models\User;
use App\Notifications\Football\GoalScoredNotification;
use App\Notifications\Football\MatchResultNotification;
use App\Services\Football\ApiSportsKeyManager;
use App\Services\Football\ApiSportsLiveScoreService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[Signature('football:poll-live')]
#[Description('Poll live scores from API-Sports and dispatch notifications')]
class PollLiveScoresCommand extends Command
{
    public function handle(
        ApiSportsLiveScoreService $liveScore,
        ApiSportsKeyManager $keyManager,
    ): int {
        if (! $keyManager->hasKeys()) {
            $this->warn('No API-Sports keys configured');

            return self::SUCCESS;
        }

        $followedLeagueApiSportsIds = DB::table('user_followed_leagues')
            ->join('football_leagues', 'football_leagues.id', '=', 'user_followed_leagues.league_id')
            ->whereNotNull('football_leagues.api_sports_id')
            ->distinct()
            ->pluck('football_leagues.api_sports_id')
            ->filter()
            ->values()
            ->toArray();

        if (empty($followedLeagueApiSportsIds)) {
            $this->info('No followed leagues, skipping');

            return self::SUCCESS;
        }

        if (! $keyManager->getActiveKey()) {
            $this->warn('No active API key (quota exhausted)');

            return self::SUCCESS;
        }

        $liveMatches = $liveScore->getLiveMatches($followedLeagueApiSportsIds);
        $this->info('Live matches: '.count($liveMatches));

        foreach ($liveMatches as $match) {
            $this->processMatch($match);
        }

        return self::SUCCESS;
    }

    private function processMatch(array $match): void
    {
        $fixtureId = $match['fixture_id'] ?? null;
        if (! $fixtureId) {
            return;
        }

        $previousState = LiveMatchState::where('fixture_external_id', (string) $fixtureId)->first();

        if (! $previousState) {
            $this->saveState($match);

            return;
        }

        $homeGoals = (int) ($match['score']['home'] ?? 0) - (int) $previousState->home_score;
        $awayGoals = (int) ($match['score']['away'] ?? 0) - (int) $previousState->away_score;

        if ($homeGoals > 0) {
            $this->dispatchGoalNotification($match, 'home', $homeGoals);
        }
        if ($awayGoals > 0) {
            $this->dispatchGoalNotification($match, 'away', $awayGoals);
        }

        $finishedStatuses = ['FT', 'AET', 'PEN'];
        if (in_array($match['status_short'] ?? '', $finishedStatuses, true)
            && ! in_array($previousState->status, $finishedStatuses, true)) {
            $this->dispatchResultNotification($match);
        }

        $this->saveState($match);
    }

    private function dispatchGoalNotification(array $match, string $side, int $goals): void
    {
        $teamApiId = $match[$side.'_team']['id'] ?? null;
        if (! $teamApiId) {
            return;
        }

        $users = User::query()
            ->whereHas('favoriteTeams', function ($q) use ($teamApiId) {
                $q->where('football_teams.api_sports_id', $teamApiId)
                    ->where('user_favorite_teams.notify_goals', true);
            })
            ->whereHas('matchNotificationSettings', function ($q) {
                $q->where('goal_notifications_enabled', true);
            })
            ->get();

        foreach ($users as $user) {
            if ($this->isInQuietHours($user)) {
                continue;
            }
            try {
                $user->notify(new GoalScoredNotification($match, $side, $goals));
            } catch (\Throwable $e) {
                Log::channel('football')->error('Goal notify failed', ['error' => $e->getMessage()]);
            }
        }
    }

    private function dispatchResultNotification(array $match): void
    {
        $homeApiId = $match['home_team']['id'] ?? null;
        $awayApiId = $match['away_team']['id'] ?? null;
        $ids = array_filter([$homeApiId, $awayApiId]);
        if (empty($ids)) {
            return;
        }

        $users = User::query()
            ->whereHas('favoriteTeams', function ($q) use ($ids) {
                $q->whereIn('football_teams.api_sports_id', $ids)
                    ->where('user_favorite_teams.notify_results', true);
            })
            ->whereHas('matchNotificationSettings', function ($q) {
                $q->where('result_notifications_enabled', true);
            })
            ->get();

        foreach ($users as $user) {
            if ($this->isInQuietHours($user)) {
                continue;
            }
            try {
                $user->notify(new MatchResultNotification($match));
            } catch (\Throwable $e) {
                Log::channel('football')->error('Result notify failed', ['error' => $e->getMessage()]);
            }
        }
    }

    private function isInQuietHours(User $user): bool
    {
        $settings = $user->matchNotificationSettings;
        if (! $settings || ! $settings->quiet_hours_enabled) {
            return false;
        }

        $start = $settings->quiet_hours_start?->format('H:i:s');
        $end = $settings->quiet_hours_end?->format('H:i:s');
        if (! $start || ! $end) {
            return false;
        }

        $now = now()->format('H:i:s');
        if ($start > $end) {
            return $now >= $start || $now <= $end;
        }

        return $now >= $start && $now <= $end;
    }

    private function saveState(array $match): void
    {
        LiveMatchState::updateOrCreate(
            ['fixture_external_id' => (string) $match['fixture_id']],
            [
                'status' => $match['status_short'] ?? $match['status'] ?? 'Unknown',
                'minute' => $match['minute'] ?? null,
                'home_score' => (int) ($match['score']['home'] ?? 0),
                'away_score' => (int) ($match['score']['away'] ?? 0),
                'last_polled_at' => now(),
                'raw_data' => $match,
            ]
        );
    }
}
