<?php

namespace Tests\Feature\Football;

use App\Jobs\Football\BroadcastMatchActivityJob;
use App\Models\Football\LiveMatchState;
use App\Services\Football\ApiSportsKeyManager;
use App\Services\Football\ApiSportsLiveScoreService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PollLiveScoresBroadcastTest extends TestCase
{
    public function test_broadcast_job_is_dispatched_when_score_changes(): void
    {
        Bus::fake();
        $this->primeFollowedLeague();
        $this->mockApiSports([$this->fixture(homeScore: 1, awayScore: 0, status: '1H', minute: 23)]);

        LiveMatchState::create([
            'fixture_external_id' => '12345',
            'status' => '1H',
            'minute' => 22,
            'home_score' => 0,
            'away_score' => 0,
            'last_polled_at' => now(),
        ]);

        $this->artisan('football:poll-live')->assertExitCode(0);

        Bus::assertDispatched(BroadcastMatchActivityJob::class, function (BroadcastMatchActivityJob $job) {
            return $job->fixtureExternalId === '12345'
                && $job->state['home_score'] === 1
                && $job->state['away_score'] === 0
                && $job->state['status_short'] === '1H';
        });
    }

    public function test_broadcast_job_is_dispatched_when_status_transitions(): void
    {
        Bus::fake();
        $this->primeFollowedLeague();
        $this->mockApiSports([$this->fixture(homeScore: 1, awayScore: 1, status: 'HT', minute: 45)]);

        LiveMatchState::create([
            'fixture_external_id' => '12345',
            'status' => '1H',
            'minute' => 45,
            'home_score' => 1,
            'away_score' => 1,
            'last_polled_at' => now(),
        ]);

        $this->artisan('football:poll-live')->assertExitCode(0);

        Bus::assertDispatched(BroadcastMatchActivityJob::class);
    }

    public function test_no_broadcast_when_state_is_unchanged(): void
    {
        Bus::fake();
        $this->primeFollowedLeague();
        $this->mockApiSports([$this->fixture(homeScore: 1, awayScore: 1, status: '2H', minute: 70)]);

        LiveMatchState::create([
            'fixture_external_id' => '12345',
            'status' => '2H',
            'minute' => 68,
            'home_score' => 1,
            'away_score' => 1,
            'last_polled_at' => now(),
        ]);

        $this->artisan('football:poll-live')->assertExitCode(0);

        Bus::assertNotDispatched(BroadcastMatchActivityJob::class);
    }

    private function primeFollowedLeague(): void
    {
        $leagueId = DB::table('football_leagues')->insertGetId([
            'external_id' => 'ext_PL',
            'api_sports_id' => 39,
            'code' => 'PL',
            'name' => 'Premier League',
            'country' => 'England',
            'type' => 'LEAGUE',
            'is_active' => true,
            'is_featured' => true,
            'display_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = $this->actingAsPlayer();
        DB::table('user_followed_leagues')->insert([
            'user_id' => $user->id,
            'league_id' => $leagueId,
            'notify_matches' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<int,array<string,mixed>>  $matches
     */
    private function mockApiSports(array $matches): void
    {
        $keyManager = $this->createMock(ApiSportsKeyManager::class);
        $keyManager->method('hasKeys')->willReturn(true);
        $keyManager->method('getActiveKey')->willReturn('test-key');

        $liveScore = $this->createMock(ApiSportsLiveScoreService::class);
        $liveScore->method('getLiveMatches')->willReturn($matches);

        $this->app->instance(ApiSportsKeyManager::class, $keyManager);
        $this->app->instance(ApiSportsLiveScoreService::class, $liveScore);
    }

    /**
     * @return array<string,mixed>
     */
    private function fixture(int $homeScore, int $awayScore, string $status, int $minute): array
    {
        return [
            'fixture_id' => 12345,
            'status' => $status,
            'status_short' => $status,
            'minute' => $minute,
            'home_team' => ['id' => 33, 'name' => 'Manchester United'],
            'away_team' => ['id' => 40, 'name' => 'Liverpool'],
            'league' => ['id' => 39, 'name' => 'Premier League'],
            'score' => ['home' => $homeScore, 'away' => $awayScore],
            'events' => [],
        ];
    }
}
