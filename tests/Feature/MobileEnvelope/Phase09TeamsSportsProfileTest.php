<?php

namespace Tests\Feature\MobileEnvelope;

use App\Models\Team;
use App\Models\TeamInvite;
use App\Models\User;
use Tests\MobileIntegrationTest;

/**
 * Phase 9 — Teams + Sports Profile — 13 covered endpoints
 * (5 from Sprint 1 + 6 added in Sprint 4 + 2 added in Sprint 5).
 *
 * Teams (Sprint 1):
 * - GET /teams
 * - GET /teams/{id}
 * - POST /teams
 *
 * Teams (Sprint 4 — new):
 * - PUT /teams/{id}
 * - DELETE /teams/{id}
 * - POST /teams/{id}/kick
 * - POST /teams/{id}/transfer-captain
 * - POST /teams/{id}/invite
 * - GET /teams/invite/{code}
 *
 * Sports Profile (Sprint 1):
 * - GET /profile/stats
 * - GET /profile/achievements
 *
 * Sports Profile (Sprint 5 — new):
 * - GET /sports-profile/me
 * - GET /sports-profile/weekly-activity
 */
class Phase09TeamsSportsProfileTest extends MobileIntegrationTest
{
    public function test_get_teams_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->getJson('/api/v1/teams');

        $this->assertEnvelope($response);
    }

    public function test_get_team_show_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->getJson('/api/v1/teams/1');

        $this->assertEnvelope($response);
    }

    public function test_post_teams_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->postJson('/api/v1/teams', []);

        $this->assertErrorEnvelope($response, 422);
    }

    public function test_get_profile_stats_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->getJson('/api/v1/profile/stats');

        $this->assertEnvelope($response);
    }

    public function test_get_profile_achievements_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->getJson('/api/v1/profile/achievements');

        $this->assertEnvelope($response);
    }

    // ========================================================================
    // Sprint 4 — six new team endpoints
    // ========================================================================

    public function test_put_teams_id_returns_envelope(): void
    {
        $captain = $this->actingAsRole('player');
        $team = Team::factory()->withCaptain($captain)->create();

        $response = $this->putJson("/api/v1/teams/{$team->id}", [
            'name' => 'Renamed Team',
        ]);

        $response->assertOk();
        $this->assertEnvelope($response);
    }

    public function test_delete_teams_id_returns_envelope(): void
    {
        $captain = $this->actingAsRole('player');
        $team = Team::factory()->withCaptain($captain)->create();

        $response = $this->deleteJson("/api/v1/teams/{$team->id}");

        $response->assertOk();
        $this->assertEnvelope($response);
    }

    public function test_post_teams_kick_returns_envelope(): void
    {
        $this->actingAsRole('player');

        // Hit a missing team to keep this a pure envelope smoke test;
        // the per-endpoint test class covers the full happy path.
        $response = $this->postJson('/api/v1/teams/999999/kick', [
            'member_id' => 1,
        ]);

        $this->assertErrorEnvelope($response, 404);
    }

    public function test_post_teams_transfer_captain_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->postJson('/api/v1/teams/999999/transfer-captain', [
            'new_captain_id' => 1,
        ]);

        $this->assertErrorEnvelope($response, 404);
    }

    public function test_post_teams_invite_returns_envelope(): void
    {
        $captain = $this->actingAsRole('player');
        $team = Team::factory()->withCaptain($captain)->create();

        $response = $this->postJson("/api/v1/teams/{$team->id}/invite", []);

        $response->assertCreated();
        $this->assertEnvelope($response);
    }

    public function test_get_teams_invite_code_returns_envelope(): void
    {
        $captain = User::factory()->create();
        $captain->assignRole('player');
        $team = Team::factory()->withCaptain($captain)->create();
        $invite = TeamInvite::factory()->create([
            'team_id' => $team->id,
            'created_by' => $captain->id,
        ]);

        $this->actingAsRole('player');

        $response = $this->getJson("/api/v1/teams/invite/{$invite->code}");

        $response->assertOk();
        $this->assertEnvelope($response);
    }

    // ========================================================================
    // Sprint 5 — two new sports-profile endpoints
    // ========================================================================

    public function test_get_sports_profile_me_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->getJson('/api/v1/sports-profile/me');

        $response->assertOk();
        $this->assertEnvelope($response);
    }

    public function test_get_sports_profile_weekly_activity_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->getJson('/api/v1/sports-profile/weekly-activity');

        $response->assertOk();
        $this->assertEnvelope($response);
    }
}
