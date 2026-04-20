<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Venue;
use App\Models\VenueWaitlist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VenueWaitlist>
 */
class VenueWaitlistFactory extends Factory
{
    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'user_id' => User::factory(),
            'booking_date' => fake()->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'),
            'start_time' => fake()->randomElement(['16:00', '17:00', '18:00', '19:00']),
            'duration_minutes' => 60,
            'expires_at' => now()->addDays(7),
        ];
    }
}
