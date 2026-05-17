<?php

namespace Tests\Feature\Football;

use App\Models\Football\MatchActivityToken;
use Tests\TestCase;

class MatchActivityTokensApiTest extends TestCase
{
    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->postJson('/api/v1/football/matches/1234/activity-token', [
            'platform' => 'android',
            'push_token' => 'tok',
        ])->assertStatus(401);

        $this->deleteJson('/api/v1/football/matches/1234/activity-token')
            ->assertStatus(401);
    }

    public function test_user_can_register_an_android_activity_token(): void
    {
        $user = $this->actingAsPlayer();

        $response = $this->postJson('/api/v1/football/matches/1234/activity-token', [
            'platform' => 'android',
            'push_token' => 'fcm-token-123',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('match_activity_tokens', [
            'user_id' => $user->id,
            'fixture_external_id' => '1234',
            'platform' => 'android',
            'push_token' => 'fcm-token-123',
            'is_active' => true,
        ]);
    }

    public function test_user_can_register_an_ios_activity_token_with_activity_id(): void
    {
        $user = $this->actingAsPlayer();

        $this->postJson('/api/v1/football/matches/9999/activity-token', [
            'platform' => 'ios',
            'push_token' => str_repeat('a', 160),
            'activity_id' => 'live-activity-abc',
        ])->assertStatus(201);

        $this->assertDatabaseHas('match_activity_tokens', [
            'user_id' => $user->id,
            'fixture_external_id' => '9999',
            'platform' => 'ios',
            'activity_id' => 'live-activity-abc',
        ]);
    }

    public function test_re_registering_updates_in_place(): void
    {
        $user = $this->actingAsPlayer();

        $this->postJson('/api/v1/football/matches/1234/activity-token', [
            'platform' => 'android',
            'push_token' => 'first-token',
        ])->assertStatus(201);

        $this->postJson('/api/v1/football/matches/1234/activity-token', [
            'platform' => 'android',
            'push_token' => 'second-token',
        ])->assertStatus(201);

        $this->assertEquals(1, MatchActivityToken::where('user_id', $user->id)->count());
        $this->assertSame('second-token', MatchActivityToken::where('user_id', $user->id)->first()->push_token);
    }

    public function test_destroy_deactivates_all_platforms_for_user_and_fixture(): void
    {
        $user = $this->actingAsPlayer();

        MatchActivityToken::create([
            'user_id' => $user->id,
            'fixture_external_id' => '1234',
            'platform' => 'ios',
            'push_token' => 'ios-tok',
            'is_active' => true,
        ]);
        MatchActivityToken::create([
            'user_id' => $user->id,
            'fixture_external_id' => '1234',
            'platform' => 'android',
            'push_token' => 'android-tok',
            'is_active' => true,
        ]);

        $this->deleteJson('/api/v1/football/matches/1234/activity-token')
            ->assertStatus(200);

        $this->assertEquals(0, MatchActivityToken::active()->where('user_id', $user->id)->count());
    }

    public function test_validation_rejects_invalid_platform(): void
    {
        $this->actingAsPlayer();

        $this->postJson('/api/v1/football/matches/1234/activity-token', [
            'platform' => 'windows',
            'push_token' => 'tok',
        ])->assertStatus(422);
    }
}
