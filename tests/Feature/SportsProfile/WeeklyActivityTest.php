<?php

namespace Tests\Feature\SportsProfile;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Services\SportsProfile\WeeklyActivityService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WeeklyActivityTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_returns_12_weeks_zero_filled_for_user_with_no_bookings(): void
    {
        $user = User::factory()->create();
        $user->assignRole('player');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/sports-profile/weekly-activity');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'weeks' => [
                        '*' => ['week_start', 'bookings_count', 'hours_played'],
                    ],
                ],
            ]);

        $weeks = $response->json('data.weeks');
        $this->assertCount(12, $weeks);

        foreach ($weeks as $w) {
            $this->assertSame(0, $w['bookings_count']);
            $this->assertSame(0, $w['hours_played']);
        }
    }

    public function test_returns_correct_counts_and_hours_for_user_with_bookings(): void
    {
        $user = User::factory()->create();
        $user->assignRole('player');

        $thisMonday = CarbonImmutable::now()->startOfWeek();

        // Two bookings this week: 60 + 90 = 150 minutes ≈ 1 + 2 = 3 hours (rounded)
        Booking::factory()->create([
            'user_id' => $user->id,
            'status' => BookingStatus::Confirmed,
            'starts_at' => $thisMonday->addDay()->setTime(10, 0),
            'ends_at' => $thisMonday->addDay()->setTime(11, 0),
            'booking_date' => $thisMonday->addDay()->toDateString(),
            'duration_minutes' => 60,
        ]);
        Booking::factory()->create([
            'user_id' => $user->id,
            'status' => BookingStatus::Completed,
            'starts_at' => $thisMonday->addDays(2)->setTime(14, 0),
            'ends_at' => $thisMonday->addDays(2)->setTime(15, 30),
            'booking_date' => $thisMonday->addDays(2)->toDateString(),
            'duration_minutes' => 90,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/sports-profile/weekly-activity');

        $response->assertOk();

        $weeks = collect($response->json('data.weeks'));
        $thisWeekBucket = $weeks->firstWhere('week_start', $thisMonday->toDateString());

        $this->assertNotNull($thisWeekBucket);
        $this->assertSame(2, $thisWeekBucket['bookings_count']);
        // 60 / 60 = 1; 90 / 60 = 1.5 → round → 2; total = 3
        $this->assertSame(3, $thisWeekBucket['hours_played']);
    }

    public function test_excludes_cancelled_and_failed_bookings(): void
    {
        $user = User::factory()->create();
        $user->assignRole('player');

        $thisMonday = CarbonImmutable::now()->startOfWeek();

        Booking::factory()->create([
            'user_id' => $user->id,
            'status' => BookingStatus::Cancelled,
            'starts_at' => $thisMonday->addDay()->setTime(10, 0),
            'ends_at' => $thisMonday->addDay()->setTime(11, 0),
            'booking_date' => $thisMonday->addDay()->toDateString(),
            'duration_minutes' => 60,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/sports-profile/weekly-activity');

        $weeks = collect($response->json('data.weeks'));
        $thisWeekBucket = $weeks->firstWhere('week_start', $thisMonday->toDateString());

        $this->assertSame(0, $thisWeekBucket['bookings_count']);
        $this->assertSame(0, $thisWeekBucket['hours_played']);
    }

    public function test_weeks_are_sorted_oldest_first(): void
    {
        $user = User::factory()->create();
        $user->assignRole('player');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/sports-profile/weekly-activity');

        $weeks = $response->json('data.weeks');
        $weekStarts = array_column($weeks, 'week_start');
        $sorted = $weekStarts;
        sort($sorted);

        $this->assertSame($sorted, $weekStarts);
    }

    public function test_cache_hit_skips_database_query_on_second_call(): void
    {
        $user = User::factory()->create();
        $user->assignRole('player');

        $service = app(WeeklyActivityService::class);
        $service->get($user); // populate cache

        $queryCount = 0;
        DB::listen(function () use (&$queryCount): void {
            $queryCount++;
        });

        $service->get($user); // cached read

        $this->assertSame(0, $queryCount, 'Second call should hit cache, not DB');
    }

    public function test_creating_a_booking_invalidates_cache(): void
    {
        $user = User::factory()->create();
        $user->assignRole('player');

        $service = app(WeeklyActivityService::class);
        $first = $service->get($user);

        $thisWeekStart = CarbonImmutable::now()->startOfWeek()->toDateString();
        $firstThisWeek = collect($first)->firstWhere('week_start', $thisWeekStart);
        $this->assertSame(0, $firstThisWeek['bookings_count']);

        // Trigger BookingObserver::created.
        Booking::factory()->create([
            'user_id' => $user->id,
            'status' => BookingStatus::Confirmed,
            'starts_at' => CarbonImmutable::now()->startOfWeek()->addDay()->setTime(10, 0),
            'ends_at' => CarbonImmutable::now()->startOfWeek()->addDay()->setTime(11, 0),
            'booking_date' => CarbonImmutable::now()->startOfWeek()->addDay()->toDateString(),
            'duration_minutes' => 60,
        ]);

        $second = $service->get($user);
        $secondThisWeek = collect($second)->firstWhere('week_start', $thisWeekStart);
        $this->assertSame(1, $secondThisWeek['bookings_count']);
    }

    public function test_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/v1/sports-profile/weekly-activity')->assertUnauthorized();
    }
}
