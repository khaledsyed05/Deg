<?php

namespace Tests\Feature;

use App\Models\Venue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Tests for venue search functionality (replaces deprecated field-deals endpoint).
 */
class FieldDealBrowseTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_venue_search_endpoint_is_public(): void
    {
        $this->getJson('/api/v1/venues/search?query=test')->assertOk();
    }

    public function test_venue_search_returns_ok_with_empty_results(): void
    {
        $this->getJson('/api/v1/venues/search?query=nonexistent_venue_xyz')
            ->assertOk()
            ->assertJson(['data' => []]);
    }

    public function test_venue_search_requires_query_parameter(): void
    {
        $this->getJson('/api/v1/venues/search')
            ->assertUnprocessable();
    }
}
