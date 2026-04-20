<?php

namespace Database\Factories;

use App\Enums\WaitlistStatus;
use App\Models\Field;
use App\Models\User;
use App\Models\WaitlistEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WaitlistEntry>
 */
class WaitlistEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'field_id' => Field::factory(),
            'desired_date' => fake()->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'),
            'desired_start_time' => '18:00:00',
            'desired_end_time' => '19:00:00',
            'status' => WaitlistStatus::Waiting,
        ];
    }
}
