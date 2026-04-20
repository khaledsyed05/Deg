<?php

namespace Tests\Feature;

use App\Models\Venue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Tests for venue available slots endpoint (replaces deprecated field-slots endpoint).
 */
class FieldAvailableSlotsTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Venue $venue;

    private string $date;

    protected function setUp(): void
    {
        parent::setUp();

        $this->venue = Venue::factory()->create([
            'status'        => 'active',
            'opening_hours' => collect(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])
                ->mapWithKeys(fn ($day) => [$day => ['open' => '08:00', 'close' => '22:00', 'closed' => false]])
                ->all(),
        ]);

        $this->date = now()->addDay()->toDateString();
    }

    public function test_endpoint_is_public_and_does_not_require_authentication(): void
    {
        $this->getJson('/api/v1/venues/' . $this->venue->id . '/slots?' . http_build_query([
            'date'             => $this->date,
            'duration_minutes' => 60,
        ]))->assertOk();
    }

    public function test_missing_date_returns_422(): void
    {
        $this->getJson('/api/v1/venues/' . $this->venue->id . '/slots?duration_minutes=60')
            ->assertUnprocessable();
    }

    public function test_missing_duration_returns_422(): void
    {
        $this->getJson('/api/v1/venues/' . $this->venue->id . '/slots?date=' . $this->date)
            ->assertUnprocessable();
    }

    public function test_nonexistent_venue_returns_404(): void
    {
        $this->getJson('/api/v1/venues/99999/slots?' . http_build_query([
            'date'             => $this->date,
            'duration_minutes' => 60,
        ]))->assertNotFound();
    }

    public function test_returns_slots_structure(): void
    {
        $response = $this->getJson('/api/v1/venues/' . $this->venue->id . '/slots?' . http_build_query([
            'date'             => $this->date,
            'duration_minutes' => 60,
        ]))->assertOk();

        $this->assertArrayHasKey('data', $response->json());
    }
}
