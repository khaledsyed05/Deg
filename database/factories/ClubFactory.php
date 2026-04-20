<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Club;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Club>
 */
class ClubFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'city_id' => City::factory(),
            'name' => ['ar' => $name, 'en' => $name],
            'slug' => Str::slug($name) . '-' . fake()->unique()->numerify('###'),
            'phone_number' => '+963944' . fake()->unique()->numerify('######'),
            'address' => fake()->streetAddress(),
            'latitude' => fake()->latitude(32, 37),
            'longitude' => fake()->longitude(35, 42),
            'status' => 'active',
            'approved_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => 'pending_approval',
            'approved_at' => null,
        ]);
    }
}
