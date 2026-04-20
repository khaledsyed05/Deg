<?php

namespace Tests\Feature;

use App\Models\Venue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AvailableSlotsServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Venue $venue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->venue = Venue::factory()->create(['status' => 'active']);
    }

    public function test_active_venue_with_no_bookings_reports_available(): void
    {
        $response = $this->getJson('/api/v1/venues/' . $this->venue->id . '/slots?' . http_build_query([
            'date'             => now()->addDays(7)->toDateString(),
            'duration_minutes' => 60,
        ]))->assertOk();

        $this->assertTrue($response->json('data.available'));
        $this->assertNull($response->json('data.unavailable_reason'));
    }

    public function test_inactive_venue_reports_unavailable_with_reason(): void
    {
        $inactiveVenue = Venue::factory()->create(['status' => 'inactive']);

        $response = $this->getJson('/api/v1/venues/' . $inactiveVenue->id . '/slots?' . http_build_query([
            'date'             => now()->addDay()->toDateString(),
            'duration_minutes' => 60,
        ]))->assertOk();

        $this->assertFalse($response->json('data.available'));
        $this->assertNotEmpty($response->json('data.unavailable_reason'));
    }

    public function test_30_minute_duration_is_valid(): void
    {
        $this->getJson('/api/v1/venues/' . $this->venue->id . '/slots?' . http_build_query([
            'date'             => now()->addDay()->toDateString(),
            'duration_minutes' => 30,
        ]))->assertOk();
    }

    public function test_240_minute_duration_is_valid(): void
    {
        $this->getJson('/api/v1/venues/' . $this->venue->id . '/slots?' . http_build_query([
            'date'             => now()->addDay()->toDateString(),
            'duration_minutes' => 240,
        ]))->assertOk();
    }

    public function test_today_date_is_valid(): void
    {
        $this->getJson('/api/v1/venues/' . $this->venue->id . '/slots?' . http_build_query([
            'date'             => now()->toDateString(),
            'duration_minutes' => 60,
        ]))->assertOk();
    }

    public function test_response_structure_contains_success_and_data(): void
    {
        $response = $this->getJson('/api/v1/venues/' . $this->venue->id . '/slots?' . http_build_query([
            'date'             => now()->addDay()->toDateString(),
            'duration_minutes' => 60,
        ]))->assertOk();

        $response->assertJsonStructure([
            'success',
            'data' => ['available', 'unavailable_reason'],
        ]);
    }
}
