<?php

namespace Tests\Feature\Venue;

use App\Models\Venue;
use Tests\TestCase;

/**
 * Regression coverage for Sprint 1's /venues/nearby 500.
 *
 * Root cause was MySQL-only trig (acos/cos/sin/radians) inside selectRaw —
 * SQLite has no equivalent, so the query exploded at compile time. The
 * scope now branches on the driver and uses a portable bounding-box on
 * SQLite.
 */
class NearbyTest extends TestCase
{
    public function test_nearby_endpoint_does_not_500_under_sqlite(): void
    {
        $response = $this->getJson('/api/v1/venues/nearby?latitude=33.5138&longitude=36.2765');

        $response->assertOk();
        $response->assertJsonStructure(['success', 'message', 'data', 'errors', 'meta']);
    }

    public function test_nearby_returns_venues_within_radius(): void
    {
        Venue::factory()->create([
            'latitude' => 33.5140,
            'longitude' => 36.2767,
            'status' => 'active',
        ]);
        Venue::factory()->create([
            'latitude' => 40.0,
            'longitude' => 36.0,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/venues/nearby?latitude=33.5138&longitude=36.2765&radius_km=5');

        $response->assertOk();
        $body = $response->json();
        $this->assertCount(1, $body['data'], 'Only the nearby venue should match.');
    }
}
