<?php

namespace Tests\Feature\MobileEnvelope;

use Tests\MobileIntegrationTest;

/**
 * Phase Matches / Waitlist / Deals — 14 covered endpoints
 * (drop the 3 waitlist gaps from the spec's gap report).
 *
 * Football matches:
 * - GET /football/matches/today
 * - GET /football/matches/upcoming
 * - GET /football/matches/yesterday
 * - GET /football/matches/{slug}
 * - GET /football/live/matches
 * - GET /football/live/matches/{id}/events
 * - GET /football/live/matches/{id}/lineups
 * - GET /football/live/matches/{id}/statistics
 * - GET /football/leagues
 * - GET /football/leagues/{id}/standings
 *
 * Waitlist (basic 3 ops):
 * - GET /waitlist
 * - POST /waitlist
 * - DELETE /waitlist/{id}
 *
 * Deals (mapped to promotions):
 * - GET /promotions
 */
class PhaseMatchesWaitlistDealsTest extends MobileIntegrationTest
{
    public function test_get_football_matches_today_returns_envelope(): void
    {
        $response = $this->getJson('/api/v1/football/matches/today');

        $this->assertEnvelope($response);
    }

    public function test_get_football_matches_upcoming_returns_envelope(): void
    {
        $response = $this->getJson('/api/v1/football/matches/upcoming');

        $this->assertEnvelope($response);
    }

    public function test_get_football_matches_yesterday_returns_envelope(): void
    {
        $response = $this->getJson('/api/v1/football/matches/yesterday');

        $this->assertEnvelope($response);
    }

    public function test_get_football_match_show_returns_envelope(): void
    {
        $response = $this->getJson('/api/v1/football/matches/sample-slug');

        $this->assertEnvelope($response);
    }

    public function test_get_football_live_matches_returns_envelope(): void
    {
        $response = $this->getJson('/api/v1/football/live/matches');

        $this->assertEnvelope($response);
    }

    public function test_get_football_live_match_events_returns_envelope(): void
    {
        $response = $this->getJson('/api/v1/football/live/matches/1/events');

        $this->assertEnvelope($response);
    }

    public function test_get_football_live_match_lineups_returns_envelope(): void
    {
        $response = $this->getJson('/api/v1/football/live/matches/1/lineups');

        $this->assertEnvelope($response);
    }

    public function test_get_football_live_match_statistics_returns_envelope(): void
    {
        $response = $this->getJson('/api/v1/football/live/matches/1/statistics');

        $this->assertEnvelope($response);
    }

    public function test_get_football_leagues_returns_envelope(): void
    {
        $response = $this->getJson('/api/v1/football/leagues');

        $this->assertEnvelope($response);
    }

    public function test_get_football_league_standings_returns_envelope(): void
    {
        $response = $this->getJson('/api/v1/football/leagues/1/standings');

        $this->assertEnvelope($response);
    }

    public function test_get_waitlist_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->getJson('/api/v1/waitlist');

        $this->assertEnvelope($response);
    }

    public function test_post_waitlist_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->postJson('/api/v1/waitlist', []);

        $this->assertErrorEnvelope($response, 422);
    }

    public function test_delete_waitlist_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->deleteJson('/api/v1/waitlist/1');

        $this->assertEnvelope($response);
    }

    public function test_get_promotions_returns_envelope(): void
    {
        $response = $this->getJson('/api/v1/promotions');

        $this->assertEnvelope($response);
    }
}
