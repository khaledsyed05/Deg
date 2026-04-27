<?php

namespace App\Services\Football;

use App\Models\User;

class MatchService
{
    public function __construct(
        private readonly FootballDataApiService $footballData,
        private readonly ApiSportsLiveScoreService $apiSports,
    ) {}

    public function getTodayMatches(?User $user = null, bool $favoritesOnly = false): array
    {
        $leagueCodes = $this->resolveLeagueCodes($user, $favoritesOnly);
        if ($favoritesOnly && empty($leagueCodes)) {
            return ['date' => today()->format('Y-m-d'), 'matches' => [], 'total' => 0];
        }

        $response = $this->footballData->getTodayMatches($leagueCodes);
        $matches = $this->enrichWithFavorites($response['matches'] ?? [], $user);

        return [
            'date' => today()->format('Y-m-d'),
            'matches' => $matches,
            'total' => count($matches),
            'cached_at' => now()->toIso8601String(),
        ];
    }

    public function getUpcomingMatches(?User $user = null, bool $favoritesOnly = false, int $days = 7): array
    {
        $leagueCodes = $this->resolveLeagueCodes($user, $favoritesOnly);
        if ($favoritesOnly && empty($leagueCodes)) {
            return ['matches' => [], 'total' => 0, 'days' => $days];
        }

        $response = $this->footballData->getUpcomingMatches($days, $leagueCodes);
        $matches = $this->enrichWithFavorites($response['matches'] ?? [], $user);

        return [
            'matches' => $matches,
            'total' => count($matches),
            'days' => $days,
        ];
    }

    public function getYesterdayResults(?User $user = null): array
    {
        $response = $this->footballData->getYesterdayResults();
        $matches = $this->enrichWithFavorites($response['matches'] ?? [], $user);

        return [
            'date' => today()->subDay()->format('Y-m-d'),
            'matches' => $matches,
            'total' => count($matches),
        ];
    }

    public function getLiveMatches(?User $user = null, bool $favoritesOnly = false): array
    {
        $leagueIds = null;

        if ($user && $favoritesOnly) {
            $leagueIds = $user->followedLeagues()
                ->whereNotNull('api_sports_id')
                ->pluck('api_sports_id')
                ->filter()
                ->values()
                ->toArray();
        }

        $matches = $this->apiSports->getLiveMatches($leagueIds);

        return [
            'matches' => $matches,
            'total' => count($matches),
            'updated_at' => now()->toIso8601String(),
            'next_update_in_seconds' => config('football.cache.live_score_ttl'),
        ];
    }

    private function resolveLeagueCodes(?User $user, bool $favoritesOnly): ?array
    {
        if (! $user || ! $favoritesOnly) {
            return null;
        }

        return $user->followedLeagues()
            ->pluck('code')
            ->filter()
            ->values()
            ->toArray();
    }

    private function enrichWithFavorites(array $matches, ?User $user): array
    {
        if (! $user) {
            return $matches;
        }

        $favoriteTeamIds = $user->favoriteTeams()
            ->pluck('external_id')
            ->toArray();

        return array_map(function ($match) use ($favoriteTeamIds) {
            $homeId = (string) ($match['homeTeam']['id'] ?? '');
            $awayId = (string) ($match['awayTeam']['id'] ?? '');
            $match['is_user_favorite'] =
                in_array($homeId, $favoriteTeamIds, true) ||
                in_array($awayId, $favoriteTeamIds, true);

            return $match;
        }, $matches);
    }
}
