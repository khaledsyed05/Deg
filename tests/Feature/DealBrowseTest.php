<?php

namespace Tests\Feature;

use App\Models\Venue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Tests for venue listing endpoint (replaces deprecated deal-browse endpoint).
 */
class DealBrowseTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_endpoint_is_public_and_does_not_require_authentication(): void
    {
        $this->getJson('/api/v1/venues')->assertOk();
    }

    public function test_returns_active_venues_successfully(): void
    {
        Venue::factory()->count(2)->create(['status' => 'active']);

        $this->getJson('/api/v1/venues')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_returns_empty_collection_when_no_active_venues_exist(): void
    {
        $this->getJson('/api/v1/venues')
            ->assertOk()
            ->assertJson(['data' => []]);
    }
}
