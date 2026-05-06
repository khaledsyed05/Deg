<?php

namespace Tests\Feature\Venue;

use App\Models\City;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Documents the existing /venues/clusters semantics so a future
 * accidental refactor can't silently regress the wire format.
 *
 * The endpoint returns city-grouped clusters (one cluster per city,
 * anchored at the city centre, with `count` = venues in that city).
 * True zoom-aware clustering is deferred to a future sprint when
 * venue density justifies it.
 */
class ClustersTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Service caches under venue_clusters_{lat}_{lng}_{radius}_{zoom};
        // wipe to avoid cross-test contamination.
        Cache::flush();
    }

    public function test_returns_documented_clusters_shape(): void
    {
        City::factory()->create([
            'name' => 'Damascus',
            'name_ar' => 'دمشق',
            'latitude' => 33.5138,
            'longitude' => 36.2765,
            'is_visible' => true,
            'venues_count' => 24,
        ]);

        $response = $this->getJson('/api/v1/venues/clusters?lat=33.5&lng=36.3&radius=50');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'clusters' => [
                        '*' => ['lat', 'lng', 'count', 'city', 'distance_km'],
                    ],
                    'total_venues',
                ],
            ])
            ->assertJsonPath('data.total_venues', 24)
            ->assertJsonPath('data.clusters.0.count', 24);
    }

    public function test_excludes_cities_with_zero_venues(): void
    {
        City::factory()->create([
            'name' => 'Damascus',
            'latitude' => 33.5138,
            'longitude' => 36.2765,
            'is_visible' => true,
            'venues_count' => 5,
        ]);
        City::factory()->create([
            'name' => 'Empty City',
            'latitude' => 33.6,
            'longitude' => 36.4,
            'is_visible' => true,
            'venues_count' => 0,
        ]);

        $response = $this->getJson('/api/v1/venues/clusters?lat=33.5&lng=36.3&radius=50');

        $response->assertOk()
            ->assertJsonPath('data.total_venues', 5)
            ->assertJsonCount(1, 'data.clusters');
    }

    public function test_zoom_param_is_accepted_but_does_not_change_shape(): void
    {
        City::factory()->create([
            'name' => 'Damascus',
            'latitude' => 33.5138,
            'longitude' => 36.2765,
            'is_visible' => true,
            'venues_count' => 12,
        ]);

        $low = $this->getJson('/api/v1/venues/clusters?lat=33.5&lng=36.3&radius=50&zoom=8');
        Cache::flush();
        $high = $this->getJson('/api/v1/venues/clusters?lat=33.5&lng=36.3&radius=50&zoom=18');

        $low->assertOk();
        $high->assertOk();

        $this->assertSame(
            $low->json('data.clusters.0.count'),
            $high->json('data.clusters.0.count'),
            'zoom is accepted but currently does not change the response shape',
        );
    }

    public function test_validation_rejects_missing_lat_lng(): void
    {
        $this->getJson('/api/v1/venues/clusters')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lat', 'lng']);
    }
}
