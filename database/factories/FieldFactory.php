<?php

namespace Database\Factories;

use App\Enums\SportType;
use App\Models\Field;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Field>
 */
class FieldFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'name' => 'Field ' . fake()->numberBetween(1, 10),
            'sport_type' => fake()->randomElement(SportType::cases()),
            'price_per_hour' => fake()->randomFloat(2, 10, 200),
            'deposit_percentage' => fake()->randomElement([0, 20, 30, 50]),
            'capacity' => fake()->optional()->numberBetween(2, 22),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
