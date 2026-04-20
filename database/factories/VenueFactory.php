<?php

namespace Database\Factories;

use App\Models\Club;
use App\Models\Venue;
use App\Models\VenueCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Venue>
 */
class VenueFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->company() . ' Stadium';

        return [
            'club_id' => Club::factory(),
            'name' => ['ar' => $name, 'en' => $name],
            'opening_hours' => collect(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])
                ->mapWithKeys(fn ($day) => [
                    $day => ['open' => '08:00', 'close' => '22:00', 'closed' => false],
                ])->all(),
            'latitude' => fake()->latitude(32, 37),
            'longitude' => fake()->longitude(35, 42),
            'price_from' => 40000,
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }
}
