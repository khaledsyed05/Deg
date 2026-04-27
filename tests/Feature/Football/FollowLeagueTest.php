<?php

namespace Tests\Feature\Football;

use App\Models\Football\League;
use Tests\TestCase;

class FollowLeagueTest extends TestCase
{
    private function makeLeague(string $code, int $order = 0): League
    {
        return League::create([
            'external_id' => 'ext_'.$code,
            'code' => $code,
            'name' => $code.' League',
            'country' => 'Test',
            'type' => 'LEAGUE',
            'is_active' => true,
            'is_featured' => true,
            'display_order' => $order,
        ]);
    }

    public function test_user_can_follow_a_league(): void
    {
        $user = $this->actingAsPlayer();
        $this->makeLeague('PL');

        $response = $this->postJson('/api/v1/football/favorites/leagues', [
            'league_code' => 'PL',
            'notify_matches' => true,
        ]);

        $response->assertStatus(201);
        $this->assertEquals(1, $user->followedLeagues()->count());
    }

    public function test_user_can_follow_multiple_leagues_with_correct_pivot_order(): void
    {
        $user = $this->actingAsPlayer();
        $pl = $this->makeLeague('PL', 1);
        $pd = $this->makeLeague('PD', 2);

        $this->postJson('/api/v1/football/favorites/leagues', ['league_code' => 'PL'])
            ->assertStatus(201);

        // The previously-failing case: ambiguous display_order column
        $this->postJson('/api/v1/football/favorites/leagues', ['league_code' => 'PD'])
            ->assertStatus(201);

        $this->assertEquals(2, $user->followedLeagues()->count());

        $second = $user->followedLeagues()->where('league_id', $pd->id)->first();
        $this->assertEquals(2, $second->pivot->display_order);
    }

    public function test_duplicate_follow_is_rejected(): void
    {
        $this->actingAsPlayer();
        $this->makeLeague('PL');

        $this->postJson('/api/v1/football/favorites/leagues', ['league_code' => 'PL'])
            ->assertStatus(201);

        $this->postJson('/api/v1/football/favorites/leagues', ['league_code' => 'PL'])
            ->assertStatus(409);
    }
}
