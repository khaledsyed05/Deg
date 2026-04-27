<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'venue_id' => function () {
                return Venue::factory()->create()->id;
            },
            'club_id' => function (array $attributes) {
                return Venue::find($attributes['venue_id'])?->club_id;
            },
            'booking_id' => function (array $attributes) {
                return Booking::factory()->create([
                    'user_id' => $attributes['user_id'],
                    'venue_id' => $attributes['venue_id'],
                ])->id;
            },
            'rating' => fake()->numberBetween(3, 5),
            'body' => fake()->sentence(),
            'comment' => fake()->paragraph(),
            'helpful_count' => 0,
            'is_anonymous' => false,
            'is_published' => true,
            'published_at' => now(),
        ];
    }
}
