<?php

namespace Database\Factories;

use App\Models\Deal;
use App\Models\Field;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deal>
 */
class DealFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $originalPrice = fake()->randomFloat(2, 50, 200);
        $startsAt = fake()->dateTimeBetween('+1 hour', '+7 days');
        $endsAt = (clone $startsAt)->modify('+1 hour');

        return [
            'field_id' => Field::factory(),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'original_price' => $originalPrice,
            'discounted_price' => round($originalPrice * 0.70, 2),
            'offer_expires_at' => (clone $startsAt)->modify('-1 hour'),
            'is_active' => true,
        ];
    }
}
