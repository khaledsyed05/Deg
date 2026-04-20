<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\Venue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Tests for venue browse with club context (replaces deprecated field-browse endpoint).
 */
class FieldBrowseTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_endpoint_is_public_and_does_not_require_authentication(): void
    {
        $this->getJson('/api/v1/venues')->assertOk();
    }

    public function test_returns_active_venues(): void
    {
        Venue::factory()->count(3)->create(['status' => 'active']);

        $response = $this->getJson('/api/v1/venues')->assertOk();

        $this->assertArrayHasKey('data', $response->json());
        $this->assertCount(3, $response->json('data'));
    }

    public function test_venues_belong_to_clubs(): void
    {
        $club  = Club::factory()->create(['status' => 'active']);
        $venue = Venue::factory()->for($club)->create(['status' => 'active']);

        $this->assertSame($club->id, $venue->club_id);
    }

    public function test_inactive_venues_are_excluded(): void
    {
        Venue::factory()->create(['status' => 'active']);
        Venue::factory()->create(['status' => 'inactive']);

        $this->getJson('/api/v1/venues')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
