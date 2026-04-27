<?php

namespace App\Services\Football;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiSportsLiveScoreService
{
    private string $baseUrl;

    public function __construct(private readonly ApiSportsKeyManager $keyManager)
    {
        $this->baseUrl = rtrim((string) config('services.api_sports.base_url'), '/');
    }

    public function getLiveMatches(?array $leagueIds = null): array
    {
        $cacheKey = 'live_matches_'.md5(implode(',', $leagueIds ?? []));

        return Cache::remember($cacheKey, config('football.cache.live_score_ttl'), function () use ($leagueIds) {
            $params = ['live' => 'all'];
            if (! empty($leagueIds)) {
                $params['league'] = implode('-', $leagueIds);
            }

            return $this->formatMatches($this->call('/fixtures', $params));
        });
    }

    public function getMatchLive(int $fixtureId): ?array
    {
        return Cache::remember("match_live_{$fixtureId}", config('football.cache.live_score_ttl'), function () use ($fixtureId) {
            $matches = $this->formatMatches($this->call('/fixtures', ['id' => $fixtureId]));

            return $matches[0] ?? null;
        });
    }

    public function getMatchEvents(int $fixtureId): array
    {
        return Cache::remember("match_events_{$fixtureId}", config('football.cache.live_score_ttl'), function () use ($fixtureId) {
            return $this->call('/fixtures/events', ['fixture' => $fixtureId]);
        });
    }

    public function getMatchStatistics(int $fixtureId): array
    {
        return Cache::remember("match_stats_{$fixtureId}", config('football.cache.live_score_ttl'), function () use ($fixtureId) {
            return $this->call('/fixtures/statistics', ['fixture' => $fixtureId]);
        });
    }

    public function getMatchLineups(int $fixtureId): array
    {
        return Cache::remember("match_lineups_{$fixtureId}", 600, function () use ($fixtureId) {
            return $this->call('/fixtures/lineups', ['fixture' => $fixtureId]);
        });
    }

    private function call(string $endpoint, array $params = []): array
    {
        $key = $this->keyManager->getActiveKey();

        if (! $key) {
            return [];
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders(['x-apisports-key' => $key])
                ->get($this->baseUrl.$endpoint, $params);

            if (! $response->successful()) {
                Log::channel('football')->error('API-Sports error', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                ]);

                return [];
            }

            return $response->json('response', []) ?? [];
        } catch (\Throwable $e) {
            Log::channel('football')->error('API-Sports exception', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function formatMatches(array $matches): array
    {
        return array_map(function ($match) {
            return [
                'fixture_id' => $match['fixture']['id'] ?? null,
                'status' => $match['fixture']['status']['long'] ?? 'Unknown',
                'status_short' => $match['fixture']['status']['short'] ?? null,
                'minute' => $match['fixture']['status']['elapsed'] ?? null,
                'date' => $match['fixture']['date'] ?? null,
                'venue' => $match['fixture']['venue'] ?? null,
                'league' => [
                    'id' => $match['league']['id'] ?? null,
                    'name' => $match['league']['name'] ?? null,
                    'logo' => $match['league']['logo'] ?? null,
                    'country' => $match['league']['country'] ?? null,
                ],
                'home_team' => [
                    'id' => $match['teams']['home']['id'] ?? null,
                    'name' => $match['teams']['home']['name'] ?? null,
                    'logo' => $match['teams']['home']['logo'] ?? null,
                ],
                'away_team' => [
                    'id' => $match['teams']['away']['id'] ?? null,
                    'name' => $match['teams']['away']['name'] ?? null,
                    'logo' => $match['teams']['away']['logo'] ?? null,
                ],
                'score' => [
                    'home' => $match['goals']['home'] ?? 0,
                    'away' => $match['goals']['away'] ?? 0,
                ],
                'events' => $match['events'] ?? [],
            ];
        }, $matches);
    }
}
