<?php

namespace Database\Factories;

use App\Models\Field;
use App\Models\FieldSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FieldSchedule>
 */
class FieldScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'field_id' => Field::factory(),
            'day_of_week' => fake()->numberBetween(0, 6),
            'opens_at' => '08:00:00',
            'closes_at' => '22:00:00',
            'is_active' => true,
        ];
    }
}
