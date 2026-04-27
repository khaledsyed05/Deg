<?php

namespace App\Console\Commands\Football;

use App\Models\Football\League;
use App\Models\Football\Team;
use App\Services\Football\FootballDataApiService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('football:sync')]
#[Description('Sync teams and leagues from football-data.org')]
class SyncFootballDataCommand extends Command
{
    public function handle(FootballDataApiService $api): int
    {
        $this->info('Starting football data sync...');

        $this->syncLeagues($api);
        $this->syncTeams($api);

        $this->info('Sync completed.');

        return self::SUCCESS;
    }

    private function syncLeagues(FootballDataApiService $api): void
    {
        $featuredCodes = config('football.featured_leagues', []);
        $response = $api->getAllAvailableLeagues();

        $count = 0;
        foreach ($response['competitions'] ?? [] as $competition) {
            $code = $competition['code'] ?? null;
            if (! $code) {
                continue;
            }

            League::updateOrCreate(
                ['external_id' => (string) $competition['id']],
                [
                    'code' => $code,
                    'name' => $competition['name'] ?? $code,
                    'country' => $competition['area']['name'] ?? 'Unknown',
                    'emblem_url' => $competition['emblem'] ?? null,
                    'type' => $competition['type'] ?? 'LEAGUE',
                    'current_season_start' => $competition['currentSeason']['startDate'] ?? null,
                    'current_season_end' => $competition['currentSeason']['endDate'] ?? null,
                    'is_active' => true,
                    'is_featured' => in_array($code, $featuredCodes, true),
                    'last_synced_at' => now(),
                ]
            );
            $count++;
        }

        $this->info("Leagues synced: {$count}");
        Log::channel('football')->info('Leagues synced', ['count' => $count]);
    }

    private function syncTeams(FootballDataApiService $api): void
    {
        $leagues = League::featured()->get();

        $totalTeams = 0;
        foreach ($leagues as $league) {
            $response = $api->getLeagueTeams($league->code);
            foreach ($response['teams'] ?? [] as $teamData) {
                Team::updateOrCreate(
                    ['external_id' => (string) $teamData['id']],
                    [
                        'name' => $teamData['name'] ?? '',
                        'short_name' => $teamData['shortName'] ?? null,
                        'tla' => $teamData['tla'] ?? null,
                        'crest_url' => $teamData['crest'] ?? null,
                        'league_id' => $league->id,
                        'country' => $teamData['area']['name'] ?? null,
                        'venue_name' => $teamData['venue'] ?? null,
                        'founded_year' => $teamData['founded'] ?? null,
                        'last_synced_at' => now(),
                    ]
                );
                $totalTeams++;
            }
        }

        $this->info("Teams synced: {$totalTeams} across {$leagues->count()} leagues");
        Log::channel('football')->info('Teams synced', ['teams' => $totalTeams, 'leagues' => $leagues->count()]);
    }
}
