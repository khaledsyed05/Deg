<?php

namespace Tests\Unit;

use App\Models\Venue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Unit-style coverage of {@see Venue::scopeWithinBounds}.
 *
 * The test class is in tests/Unit/ but it touches the database, so it
 * extends Tests\TestCase (not PHPUnit\Framework\TestCase) — sharing
 * the Laravel app bootstrap with feature tests.
 */
class VenueWithinBoundsScopeTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_venues_inside_the_box(): void
    {
        $inside = Venue::factory()->create(['latitude' => 33.51, 'longitude' => 36.30]);
        $alsoInside = Venue::factory()->create(['latitude' => 33.55, 'longitude' => 36.25]);

        $hits = Venue::query()
            ->withinBounds(north: 33.6, south: 33.5, east: 36.4, west: 36.2)
            ->pluck('id')
            ->all();

        $this->assertContains($inside->id, $hits);
        $this->assertContains($alsoInside->id, $hits);
    }

    public function test_excludes_venues_outside_the_box(): void
    {
        $outside = Venue::factory()->create(['latitude' => 30.00, 'longitude' => 31.00]);

        $hits = Venue::query()
            ->withinBounds(north: 33.6, south: 33.5, east: 36.4, west: 36.2)
            ->pluck('id')
            ->all();

        $this->assertNotContains($outside->id, $hits);
    }

    public function test_includes_venues_exactly_on_the_boundary(): void
    {
        $north = Venue::factory()->create(['latitude' => 33.60, 'longitude' => 36.30]);
        $south = Venue::factory()->create(['latitude' => 33.50, 'longitude' => 36.30]);
        $east = Venue::factory()->create(['latitude' => 33.55, 'longitude' => 36.40]);
        $west = Venue::factory()->create(['latitude' => 33.55, 'longitude' => 36.20]);

        $hits = Venue::query()
            ->withinBounds(north: 33.6, south: 33.5, east: 36.4, west: 36.2)
            ->pluck('id')
            ->all();

        $this->assertContains($north->id, $hits);
        $this->assertContains($south->id, $hits);
        $this->assertContains($east->id, $hits);
        $this->assertContains($west->id, $hits);
    }

    public function test_excludes_venues_with_null_latitude_or_longitude(): void
    {
        $nullLat = Venue::factory()->create(['latitude' => null, 'longitude' => 36.30]);
        $nullLng = Venue::factory()->create(['latitude' => 33.55, 'longitude' => null]);
        $bothNull = Venue::factory()->create(['latitude' => null, 'longitude' => null]);

        $hits = Venue::query()
            ->withinBounds(north: 33.6, south: 33.5, east: 36.4, west: 36.2)
            ->pluck('id')
            ->all();

        $this->assertNotContains($nullLat->id, $hits);
        $this->assertNotContains($nullLng->id, $hits);
        $this->assertNotContains($bothNull->id, $hits);
    }

    public function test_returns_empty_when_box_has_no_venues(): void
    {
        Venue::factory()->create(['latitude' => 33.51, 'longitude' => 36.30]);

        $hits = Venue::query()
            ->withinBounds(north: 0.1, south: 0.0, east: 0.1, west: 0.0)
            ->pluck('id')
            ->all();

        $this->assertSame([], $hits);
    }
}
