<?php

namespace Tests\Feature\Venue;

use App\Models\Venue;
use App\Models\VenueCategory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ByBoundsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_active_venues_within_bounds(): void
    {
        $this->actingAsPlayer();

        $inside = Venue::factory()->create([
            'latitude' => 33.51, 'longitude' => 36.30, 'status' => 'active',
        ]);
        Venue::factory()->create([
            'latitude' => 30.00, 'longitude' => 31.00, 'status' => 'active',
        ]);

        $response = $this->getJson(
            '/api/v1/venues/by-bounds?north=33.6&south=33.5&east=36.4&west=36.2',
        );

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'slug',
                        'name',
                        'category_id',
                        'latitude',
                        'longitude',
                        'price_from',
                        'rating',
                        'is_active',
                    ],
                ],
            ]);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($inside->id, $ids);
        $this->assertCount(1, $ids);
    }

    public function test_accepts_ne_lat_ne_lng_sw_lat_sw_lng_alias(): void
    {
        $this->actingAsPlayer();

        $inside = Venue::factory()->create([
            'latitude' => 33.51, 'longitude' => 36.30, 'status' => 'active',
        ]);

        $response = $this->getJson(
            '/api/v1/venues/by-bounds?ne_lat=33.6&ne_lng=36.4&sw_lat=33.5&sw_lng=36.2',
        );

        $response->assertOk();
        $this->assertSame($inside->id, $response->json('data.0.id'));
    }

    public function test_excludes_inactive_venues(): void
    {
        $this->actingAsPlayer();

        Venue::factory()->create([
            'latitude' => 33.51, 'longitude' => 36.30, 'status' => 'inactive',
        ]);
        $active = Venue::factory()->create([
            'latitude' => 33.52, 'longitude' => 36.31, 'status' => 'active',
        ]);

        $response = $this->getJson(
            '/api/v1/venues/by-bounds?north=33.6&south=33.5&east=36.4&west=36.2',
        );

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertSame([$active->id], $ids);
    }

    public function test_returns_empty_array_when_box_has_no_venues(): void
    {
        $this->actingAsPlayer();

        Venue::factory()->create(['latitude' => 33.51, 'longitude' => 36.30, 'status' => 'active']);

        $response = $this->getJson(
            '/api/v1/venues/by-bounds?north=0.1&south=0.0&east=0.1&west=0.0',
        );

        $response->assertOk();
        $this->assertSame([], $response->json('data'));
    }

    public function test_validation_rejects_missing_corners(): void
    {
        $this->actingAsPlayer();

        $this->getJson('/api/v1/venues/by-bounds')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['north', 'south', 'east', 'west']);
    }

    public function test_validation_rejects_north_le_south(): void
    {
        $this->actingAsPlayer();

        $this->getJson('/api/v1/venues/by-bounds?north=33.4&south=33.5&east=36.4&west=36.2')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['north']);
    }

    public function test_validation_rejects_east_le_west(): void
    {
        $this->actingAsPlayer();

        $this->getJson('/api/v1/venues/by-bounds?north=33.6&south=33.5&east=36.2&west=36.4')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['east']);
    }

    public function test_validation_rejects_lat_out_of_range(): void
    {
        $this->actingAsPlayer();

        $this->getJson('/api/v1/venues/by-bounds?north=91&south=89&east=36.4&west=36.2')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['north']);
    }

    public function test_filters_by_category_id(): void
    {
        $this->actingAsPlayer();

        $catA = VenueCategory::factory()->create();
        $catB = VenueCategory::factory()->create();

        $venueA = Venue::factory()->create([
            'category_id' => $catA->id,
            'latitude' => 33.51, 'longitude' => 36.30, 'status' => 'active',
        ]);
        Venue::factory()->create([
            'category_id' => $catB->id,
            'latitude' => 33.52, 'longitude' => 36.31, 'status' => 'active',
        ]);

        $response = $this->getJson(
            "/api/v1/venues/by-bounds?north=33.6&south=33.5&east=36.4&west=36.2&category_id={$catA->id}",
        );

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertSame([$venueA->id], $ids);
    }

    public function test_respects_explicit_limit_param(): void
    {
        $this->actingAsPlayer();

        Venue::factory()->count(5)->create([
            'latitude' => 33.51, 'longitude' => 36.30, 'status' => 'active',
        ]);

        $response = $this->getJson(
            '/api/v1/venues/by-bounds?north=33.6&south=33.5&east=36.4&west=36.2&limit=3',
        );

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_default_limit_caps_at_200(): void
    {
        $this->actingAsPlayer();

        // Sanity: 5 active venues should all return when no limit is set.
        Venue::factory()->count(5)->create([
            'latitude' => 33.51, 'longitude' => 36.30, 'status' => 'active',
        ]);

        $response = $this->getJson(
            '/api/v1/venues/by-bounds?north=33.6&south=33.5&east=36.4&west=36.2',
        );

        $response->assertOk();
        $this->assertCount(5, $response->json('data'));
    }

    public function test_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/v1/venues/by-bounds?north=33.6&south=33.5&east=36.4&west=36.2')
            ->assertUnauthorized();
    }
}
