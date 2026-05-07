<?php

namespace Tests\Feature\Venue;

use App\Models\Venue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Performance budget for /venues/by-bounds.
 *
 * Goal: a Damascus-area viewport with 200 active venues must respond
 * in under 100ms locally / 250ms in CI. The composite index on
 * (latitude, longitude) added in Sprint 6 B1 is what makes that
 * achievable; if the test ever fails, the first thing to check is
 * whether the query plan still uses that index (EXPLAIN /
 * EXPLAIN QUERY PLAN).
 *
 * The test is deliberately lenient (250ms) so containerized SQLite
 * runners on slow CI don't false-fail. Local MySQL runs comfortably
 * in <50ms. We measure the HTTP round-trip including the test
 * framework overhead — not just the SQL — so the budget reflects
 * what mobile actually sees.
 */
class ByBoundsPerformanceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_by_bounds_responds_in_budget_with_200_venues(): void
    {
        $this->actingAsPlayer();

        // Damascus area: 33.40..33.65 lat / 36.10..36.45 lng
        $venues = [];
        for ($i = 0; $i < 200; $i++) {
            $venues[] = [
                'latitude' => 33.40 + (mt_rand(0, 25000) / 100000),
                'longitude' => 36.10 + (mt_rand(0, 35000) / 100000),
            ];
        }
        Venue::factory()
            ->count(200)
            ->sequence(...$venues)
            ->state(['status' => 'active'])
            ->create();

        // Warm the model autoloader / route cache so the first hit
        // doesn't pay the cold-start cost.
        $this->getJson('/api/v1/venues/by-bounds?north=33.65&south=33.40&east=36.45&west=36.10')
            ->assertOk();

        $start = microtime(true);
        $response = $this->getJson('/api/v1/venues/by-bounds?north=33.65&south=33.40&east=36.45&west=36.10');
        $elapsedMs = (microtime(true) - $start) * 1000;

        $response->assertOk();
        $count = count($response->json('data'));
        $this->assertGreaterThanOrEqual(180, $count, 'expected ~200 venues to land inside the box');

        $budgetMs = 250;
        $this->assertLessThan(
            $budgetMs,
            $elapsedMs,
            sprintf('by-bounds took %.1fms with %d venues; budget is %dms', $elapsedMs, $count, $budgetMs),
        );
    }
}
