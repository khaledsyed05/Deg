<?php

namespace Tests\Feature\MobileEnvelope;

use Tests\MobileIntegrationTest;

/**
 * Phase 9 — Teams + Sports Profile — 5 covered endpoints
 * (rest are gaps; later sprint work).
 *
 * Teams:
 * - GET /teams
 * - GET /teams/{id}
 * - POST /teams
 *
 * Sports Profile:
 * - GET /profile/stats
 * - GET /profile/achievements
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
}
