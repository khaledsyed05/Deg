<?php

namespace Tests\Feature;

use App\Models\Venue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AvailabilityServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Venue $venue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->venue = Venue::factory()->create(['status' => 'active']);
    }

    public function test_missing_date_returns_422(): void
    {
        $this->getJson('/api/v1/venues/' . $this->venue->id . '/slots?duration_minutes=60')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date']);
    }

    public function test_missing_duration_returns_422(): void
    {
        $this->getJson('/api/v1/venues/' . $this->venue->id . '/slots?date=' . now()->addDay()->toDateString())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['duration_minutes']);
    }

    public function test_past_date_returns_422(): void
    {
        $this->getJson('/api/v1/venues/' . $this->venue->id . '/slots?' . http_build_query([
            'date'             => now()->subDay()->toDateString(),
            'duration_minutes' => 60,
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['date']);
    }

    public function test_invalid_duration_not_multiple_of_30_returns_422(): void
    {
        $this->getJson('/api/v1/venues/' . $this->venue->id . '/slots?' . http_build_query([
            'date'             => now()->addDay()->toDateString(),
            'duration_minutes' => 45,
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['duration_minutes']);
    }

    public function test_duration_exceeding_240_returns_422(): void
    {
        $this->getJson('/api/v1/venues/' . $this->venue->id . '/slots?' . http_build_query([
            'date'             => now()->addDay()->toDateString(),
            'duration_minutes' => 270,
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['duration_minutes']);
    }

    public function test_valid_request_returns_ok_with_availability_data(): void
    {
        $this->getJson('/api/v1/venues/' . $this->venue->id . '/slots?' . http_build_query([
            'date'             => now()->addDay()->toDateString(),
            'duration_minutes' => 60,
        ]))->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['available', 'unavailable_reason'],
            ]);
    }

    public function test_nonexistent_venue_returns_404(): void
    {
        $this->getJson('/api/v1/venues/99999/slots?' . http_build_query([
            'date'             => now()->addDay()->toDateString(),
            'duration_minutes' => 60,
        ]))->assertNotFound();
    }

    public function test_inactive_venue_slot_is_unavailable(): void
    {
        $inactiveVenue = Venue::factory()->create(['status' => 'inactive']);

        $response = $this->getJson('/api/v1/venues/' . $inactiveVenue->id . '/slots?' . http_build_query([
            'date'             => now()->addDay()->toDateString(),
            'duration_minutes' => 60,
        ]))->assertOk();

        $this->assertFalse($response->json('data.available'));
    }
}
