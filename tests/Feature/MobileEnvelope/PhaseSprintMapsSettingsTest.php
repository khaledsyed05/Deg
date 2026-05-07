<?php

namespace Tests\Feature\MobileEnvelope;

use Tests\MobileIntegrationTest;

/**
 * Sprint: Maps + Filters + Settings (4 endpoints).
 *
 * Per BACKEND_REQUIREMENTS.md Appendix D, this phase is composed of two
 * map endpoints (gaps in the spec) and two profile endpoints already
 * tested in Phase 1. The "covered" set per the coverage table is
 * cities + neighborhoods + clusters + by-bounds.
 *
 * - GET /cities
 * - GET /cities/{id}/neighborhoods
 * - GET /venues/clusters
 * - GET /venues/by-bounds
 */
class PhaseSprintMapsSettingsTest extends MobileIntegrationTest
{
    public function test_get_cities_returns_envelope(): void
    {
        $response = $this->getJson('/api/v1/cities');

        $response->assertOk();
        $this->assertEnvelope($response);
    }

    public function test_get_city_neighborhoods_returns_envelope(): void
    {
        $response = $this->getJson('/api/v1/cities/1/neighborhoods');

        $this->assertEnvelope($response);
    }

    public function test_get_venues_clusters_returns_envelope(): void
    {
        $response = $this->getJson('/api/v1/venues/clusters?lat=33.5&lng=36.3');

        $response->assertOk();
        $this->assertEnvelope($response);
    }

    public function test_get_venues_by_bounds_returns_envelope(): void
    {
        // Sprint 6: route now exists and requires Bearer auth.
        $this->actingAsRole('player');

        $response = $this->getJson('/api/v1/venues/by-bounds?north=34&south=33&east=37&west=36');

        $response->assertOk();
        $this->assertEnvelope($response);
    }
}
