<?php

namespace Database\Factories;

use App\Models\Venue;
use App\Models\VenuePricingTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VenuePricingTier>
 */
class VenuePricingTierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'name' => ['ar' => 'السعر الأساسي', 'en' => 'Standard Rate'],
            'day_type' => 'all_days',
            'start_time' => '08:00',
            'end_time' => '22:00',
            'duration_minutes' => 60,
            'price' => fake()->numberBetween(30000, 80000),
            'is_active' => true,
            'order_column' => 1,
        ];
    }
}
