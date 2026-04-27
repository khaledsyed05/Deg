<?php

namespace Database\Factories;

use App\Models\Club;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(3);
        $start = now()->addDays(fake()->numberBetween(3, 30))->setTime(18, 0);

        return [
            'club_id' => Club::factory(),
            'title' => $title,
            'title_ar' => 'فعالية '.fake()->word(),
            'description' => fake()->paragraph(),
            'description_ar' => 'وصف الفعالية',
            'type' => fake()->randomElement(['tournament', 'training', 'social', 'workshop']),
            'sport_type' => 'football',
            'starts_at' => $start,
            'ends_at' => (clone $start)->addHours(4),
            'registration_closes_at' => (clone $start)->subDay(),
            'max_participants' => 16,
            'min_participants' => 4,
            'current_participants' => 0,
            'registration_fee' => 0,
            'participant_type' => 'individual',
            'status' => 'open',
            'is_published' => true,
        ];
    }

    public function paid(int $fee = 25000): static
    {
        return $this->state(fn () => ['registration_fee' => $fee]);
    }

    public function full(): static
    {
        return $this->state(fn (array $attrs) => [
            'current_participants' => $attrs['max_participants'],
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => 'closed',
            'registration_closes_at' => now()->subDay(),
        ]);
    }
}
