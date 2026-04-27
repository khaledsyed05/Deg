<?php

namespace Tests\Feature\Phase17\Venue;

use App\Models\User;
use App\Models\Venue;
use App\Models\VenueView;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class VenueExtrasTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_popular_venues_returns_active_only(): void
    {
        Venue::factory()->create(['status' => 'active']);
        Venue::factory()->create(['status' => 'inactive']);

        $response = $this->getJson('/api/v1/venues/popular?limit=10')->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_recently_viewed_requires_auth(): void
    {
        $this->getJson('/api/v1/venues/recently-viewed')->assertUnauthorized();
    }

    public function test_recently_viewed_returns_user_views(): void
    {
        $user = User::factory()->create();
        $venue = Venue::factory()->create(['status' => 'active']);

        VenueView::create([
            'user_id' => $user->id,
            'venue_id' => $venue->id,
            'viewed_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/venues/recently-viewed')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
    }

    public function test_similar_venues_endpoint(): void
    {
        $venue = Venue::factory()->create(['status' => 'active']);
        Venue::factory()->count(2)->create(['status' => 'active', 'club_id' => $venue->club_id]);

        $response = $this->getJson("/api/v1/venues/{$venue->slug}/similar")->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_venue_photos_returns_structure(): void
    {
        $venue = Venue::factory()->create(['status' => 'active']);

        $this->getJson("/api/v1/venues/{$venue->slug}/photos")
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['venue_id', 'venue_slug', 'photos', 'total'],
            ]);
    }
}
