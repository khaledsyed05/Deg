<?php

namespace Tests\Feature\MobileEnvelope;

use Tests\MobileIntegrationTest;

/**
 * Phase 2 — Home + Discovery (10 endpoints).
 *
 * - GET /content/banners
 * - GET /content/featured
 * - GET /categories
 * - GET /venues/featured
 * - GET /venues/popular
 * - GET /venues/nearby
 * - GET /venues/recently-viewed (auth)
 * - GET /venues/search
 * - GET /promotions/featured
 * - GET /events
 */
class Phase02HomeTest extends MobileIntegrationTest
{
    public function test_get_content_banners_returns_envelope(): void
    {
        $response = $this->getJson('/api/v1/content/banners');

        $response->assertOk();
        $this->assertEnvelope($response);
    }

    public function test_get_content_featured_returns_envelope(): void
    {
        $response = $this->getJson('/api/v1/content/featured');

        $response->assertOk();
        $this->assertEnvelope($response);
    }

    public function test_get_categories_returns_envelope(): void
    {
        $response = $this->getJson('/api/v1/categories');

        $response->assertOk();
        $this->assertEnvelope($response);
    }

    public function test_get_venues_featured_returns_envelope(): void
    {
        $response = $this->getJson('/api/v1/venues/featured');

        $response->assertOk();
        $this->assertEnvelope($response);
    }

    public function test_get_venues_popular_returns_envelope(): void
    {
        $response = $this->getJson('/api/v1/venues/popular');

        $response->assertOk();
        $this->assertEnvelope($response);
    }

    public function test_get_venues_nearby_returns_envelope(): void
    {
        $response = $this->getJson('/api/v1/venues/nearby?latitude=33.5138&longitude=36.2765');

        $response->assertOk();
        $this->assertEnvelope($response);
    }

    public function test_get_venues_recently_viewed_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->getJson('/api/v1/venues/recently-viewed');

        $response->assertOk();
        $this->assertEnvelope($response);
    }

    public function test_get_venues_search_returns_envelope(): void
    {
        $response = $this->getJson('/api/v1/venues/search?query=test');

        $response->assertOk();
        $this->assertEnvelope($response);
    }

    public function test_get_promotions_featured_returns_envelope(): void
    {
        $response = $this->getJson('/api/v1/promotions/featured');

        $response->assertOk();
        $this->assertEnvelope($response);
    }

    public function test_get_events_returns_envelope(): void
    {
        $response = $this->getJson('/api/v1/events');

        $response->assertOk();
        $this->assertEnvelope($response);
    }
}
