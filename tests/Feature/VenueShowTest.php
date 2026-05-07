<?php

namespace Tests\Feature;

use App\Models\Venue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class VenueShowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_endpoint_is_public_and_does_not_require_authentication(): void
    {
        $venue = Venue::factory()->create();

        $this->getJson('/api/v1/venues/'.$venue->slug)->assertOk();
    }

    public function test_returns_a_venue_successfully(): void
    {
        $venue = Venue::factory()->create();

        $this->getJson('/api/v1/venues/'.$venue->slug)
            ->assertOk()
            ->assertJsonPath('data.id', $venue->id);
    }

    public function test_nonexistent_venue_returns_404(): void
    {
        $this->getJson('/api/v1/venues/99999')->assertNotFound();
    }

    public function test_response_does_not_expose_internal_fields(): void
    {
        $venue = Venue::factory()->create();

        $response = $this->getJson('/api/v1/venues/'.$venue->slug)->assertOk();

        $data = $response->json('data');
        $this->assertArrayNotHasKey('club_id', $data);
    }
}
