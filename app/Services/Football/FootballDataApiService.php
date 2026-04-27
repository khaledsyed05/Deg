<?php

namespace App\Services\Football;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FootballDataApiService
{
    private ?string $apiKey;

    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.football_data.api_key');
        $this->baseUrl = rtrim((string) config('services.football_data.base_url'), '/');
    }

    public function getTodayMatches(?array $leagueCodes = null): array
    {
        $cacheKey = 'fd_matches_today_'.md5(implode(',', $leagueCodes ?? []));

        return Cache::remember($cacheKey, config('football.cache.today_matches_ttl'), function () use ($leagueCodes) {
            $params = [
                'dateFrom' => today()->format('Y-m-d'),
                'dateTo' => today()->format('Y-m-d'),
            ];
            if ($leagueCodes) {
                $params['competitions'] = implode(',', $leagueCodes);
            }

            return $this->call('/matches', $params);
        });
    }

    public function getUpcomingMatches(int $days = 7, ?array $leagueCodes = null): array
    {
        $cacheKey = 'fd_matches_upcoming_'.$days.'_'.md5(implode(',', $leagueCodes ?? []));

        return Cache::remember($cacheKey, config('football.cache.upcoming_matches_ttl'), function () use ($days, $leagueCodes) {
            $params = [
                'dateFrom' => today()->addDay()->format('Y-m-d'),
                'dateTo' => today()->addDays($days)->format('Y-m-d'),
            ];
            if ($leagueCodes) {
                $params['competitions'] = implode(',', $leagueCodes);
            }

            return $this->call('/matches', $params);
        });
    }

    public function getYesterdayResults(?array $leagueCodes = null): array
    {
        $cacheKey = 'fd_matches_yesterday_'.md5(implode(',', $leagueCodes ?? []));

        return Cache::remember($cacheKey, 3600, function () use ($leagueCodes) {
            $params = [
                'dateFrom' => today()->subDay()->format('Y-m-d'),
                'dateTo' => today()->subDay()->format('Y-m-d'),
                'status' => 'FINISHED',
            ];
            if ($leagueCodes) {
                $params['competitions'] = implode(',', $leagueCodes);
            }

            return $this->call('/matches', $params);
        });
    }

    public function getMatch(string $externalId): array
    {
        return Cache::remember("fd_match_{$externalId}", 60, function () use ($externalId) {
            return $this->call("/matches/{$externalId}");
        });
    }

    public function getLeagueStandings(string $code): array
    {
        return Cache::remember("fd_standings_{$code}", config('football.cache.standings_ttl'), function () use ($code) {
            return $this->call("/competitions/{$code}/standings");
        });
    }

    public function getLeagueScorers(string $code, int $limit = 10): array
    {
        return Cache::remember("fd_scorers_{$code}_{$limit}", config('football.cache.standings_ttl'), function () use ($code, $limit) {
            return $this->call("/competitions/{$code}/scorers", ['limit' => $limit]);
        });
    }

    public function getLeagueMatches(string $code, ?string $status = null): array
    {
        $cacheKey = "fd_league_{$code}_matches_".($status ?? 'all');

        return Cache::remember($cacheKey, 600, function () use ($code, $status) {
            $params = [];
            if ($status) {
                $params['status'] = $status;
            }

            return $this->call("/competitions/{$code}/matches", $params);
        });
    }

    public function getLeagueTeams(string $code): array
    {
        return Cache::remember("fd_teams_{$code}", config('football.cache.teams_ttl'), function () use ($code) {
            return $this->call("/competitions/{$code}/teams");
        });
    }

    public function getLeague(string $code): array
    {
        return Cache::remember("fd_league_{$code}", config('football.cache.teams_ttl'), function () use ($code) {
            return $this->call("/competitions/{$code}");
        });
    }

    public function getTeam(int $teamId): array
    {
        return Cache::remember("fd_team_{$teamId}", config('football.cache.teams_ttl'), function () use ($teamId) {
            return $this->call("/teams/{$teamId}");
        });
    }

    public function getTeamMatches(int $teamId, string $status = 'SCHEDULED', int $limit = 10): array
    {
        return Cache::remember("fd_team_{$teamId}_matches_{$status}_{$limit}", 1800, function () use ($teamId, $status, $limit) {
            return $this->call("/teams/{$teamId}/matches", [
                'status' => $status,
                'limit' => $limit,
            ]);
        });
    }

    public function getAllAvailableLeagues(): array
    {
        return Cache::remember('fd_all_leagues', 86400, function () {
            return $this->call('/competitions');
        });
    }

    private function call(string $endpoint, array $params = []): array
    {
        if (empty($this->apiKey)) {
            Log::channel('football')->warning('football-data.org API key not configured', ['endpoint' => $endpoint]);

            return [];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders(['X-Auth-Token' => $this->apiKey])
                ->get($this->baseUrl.$endpoint, $params);

            if (! $response->successful()) {
                Log::channel('football')->error('football-data.org API error', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            return $response->json() ?? [];
        } catch (\Throwable $e) {
            Log::channel('football')->error('football-data.org exception', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
