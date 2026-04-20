<?php

namespace Tests\Feature;

use App\Models\Venue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class VenueBrowseTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_endpoint_is_public_and_does_not_require_authentication(): void
    {
        $this->getJson('/api/v1/venues')->assertOk();
    }

    public function test_returns_active_venues_successfully(): void
    {
        Venue::factory()->count(3)->create(['status' => 'active']);

        $this->getJson('/api/v1/venues')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_returns_empty_collection_when_no_active_venues_exist(): void
    {
        Venue::factory()->count(2)->create(['status' => 'inactive']);

        $this->getJson('/api/v1/venues')
            ->assertOk()
            ->assertJson(['data' => []]);
    }

    public function test_response_does_not_expose_internal_fields(): void
    {
        Venue::factory()->create(['status' => 'active']);

        $response = $this->getJson('/api/v1/venues')->assertOk();

        $venue = $response->json('data.0');
        $this->assertArrayNotHasKey('club_id', $venue);
    }

    public function test_excludes_inactive_venues(): void
    {
        Venue::factory()->create(['status' => 'active']);
        Venue::factory()->create(['status' => 'inactive']);

        $this->getJson('/api/v1/venues')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
