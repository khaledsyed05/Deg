<?php

namespace Tests\Feature;

use App\Models\Venue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Tests for venue search endpoint (replaces deprecated venue-deals endpoint).
 */
class VenueDealBrowseTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_venue_search_is_public_and_does_not_require_authentication(): void
    {
        $this->getJson('/api/v1/venues/search?query=ملعب')->assertOk();
    }

    public function test_venue_search_returns_matching_venues(): void
    {
        Venue::factory()->create([
            'name'   => ['ar' => 'ملعب الجلاء', 'en' => 'Al-Jaish Stadium'],
            'status' => 'active',
        ]);
        Venue::factory()->create([
            'name'   => ['ar' => 'صالة الكرة', 'en' => 'Basketball Hall'],
            'status' => 'active',
        ]);

        $this->getJson('/api/v1/venues/search?query=Jaish')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_venue_search_returns_empty_when_no_match(): void
    {
        Venue::factory()->create(['status' => 'active']);

        $this->getJson('/api/v1/venues/search?query=xyznonexistent')
            ->assertOk()
            ->assertJson(['data' => []]);
    }
}
