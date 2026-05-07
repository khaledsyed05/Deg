<?php

namespace Tests\Feature\SportsProfile;

use App\Enums\AchievementType;
use App\Enums\BookingStatus;
use App\Models\Achievement;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class MeTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_me_returns_documented_shape(): void
    {
        $user = User::factory()->create();
        $user->assignRole('player');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/sports-profile/me');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'stats' => [
                        'total_bookings',
                        'completed_bookings',
                        'cancelled_bookings',
                        'total_hours_played',
                        'total_spent',
                        'favorite_sport',
                        'favorite_venue',
                        'average_rating_given',
                        'current_streak',
                        'bookings_this_month',
                    ],
                    'achievements',
                    'total_points',
                    'recent_bookings',
                ],
            ])
            ->assertJsonPath('data.stats.total_bookings', 0)
            ->assertJsonPath('data.total_points', 0);

        $this->assertSame([], $response->json('data.achievements'));
        $this->assertSame([], $response->json('data.recent_bookings'));
    }

    public function test_me_caps_recent_bookings_at_five(): void
    {
        $user = User::factory()->create();
        $user->assignRole('player');

        // 7 past bookings.
        Booking::factory()
            ->count(7)
            ->state([
                'user_id' => $user->id,
                'status' => BookingStatus::Completed,
                'starts_at' => now()->subDays(3),
                'ends_at' => now()->subDays(3)->addHour(),
                'booking_date' => now()->subDays(3)->format('Y-m-d'),
            ])
            ->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/sports-profile/me');

        $response->assertOk();
        $this->assertCount(5, $response->json('data.recent_bookings'));
    }

    public function test_me_includes_unlocked_achievement_points(): void
    {
        $user = User::factory()->create();
        $user->assignRole('player');

        Achievement::create([
            'user_id' => $user->id,
            'type' => AchievementType::FirstBooking->value,
            'progress' => 1,
            'target' => 1,
            'unlocked_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/sports-profile/me');

        $response->assertOk()
            ->assertJsonPath('data.total_points', AchievementType::FirstBooking->metadata()['points']);

        $this->assertCount(1, $response->json('data.achievements'));
        $this->assertTrue($response->json('data.achievements.0.unlocked'));
    }

    public function test_me_returns_envelope_for_user_with_no_data(): void
    {
        $user = User::factory()->create();
        $user->assignRole('player');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/sports-profile/me');

        $response->assertOk()
            ->assertJsonPath('data.stats.total_bookings', 0)
            ->assertJsonPath('data.stats.completed_bookings', 0)
            ->assertJsonPath('data.total_points', 0);
    }

    public function test_me_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/v1/sports-profile/me')->assertUnauthorized();
    }
}
