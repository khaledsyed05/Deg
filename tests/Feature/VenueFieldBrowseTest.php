<?php

namespace Tests\Feature;

use App\Models\Venue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Tests for venue available slots endpoint (replaces deprecated field-browse endpoint).
 */
class VenueFieldBrowseTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_slots_endpoint_is_public_and_does_not_require_authentication(): void
    {
        $venue = Venue::factory()->create(['status' => 'active']);

        $this->getJson('/api/v1/venues/'.$venue->slug.'/slots?'.http_build_query([
            'date' => now()->addDay()->toDateString(),
            'duration_minutes' => 60,
        ]))->assertOk();
    }

    public function test_nonexistent_venue_returns_404(): void
    {
        $this->getJson('/api/v1/venues/99999/slots?'.http_build_query([
            'date' => now()->addDay()->toDateString(),
            'duration_minutes' => 60,
        ]))->assertNotFound();
    }

    public function test_missing_date_parameter_returns_422(): void
    {
        $venue = Venue::factory()->create(['status' => 'active']);

        $this->getJson('/api/v1/venues/'.$venue->slug.'/slots?duration_minutes=60')
            ->assertUnprocessable();
    }

    public function test_missing_duration_parameter_returns_422(): void
    {
        $venue = Venue::factory()->create(['status' => 'active']);

        $this->getJson('/api/v1/venues/'.$venue->slug.'/slots?date='.now()->addDay()->toDateString())
            ->assertUnprocessable();
    }
}
