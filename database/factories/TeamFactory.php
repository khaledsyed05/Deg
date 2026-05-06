<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\VenueCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true).' FC',
            'description' => fake()->sentence(),
            'type' => 'casual',
            'sport_category_id' => VenueCategory::factory(),
            'captain_id' => User::factory(),
            'max_members' => 20,
            'is_public' => false,
            'requires_approval' => false,
            'total_members' => 1,
        ];
    }

    public function withCaptain(User $captain): static
    {
        return $this->state(fn () => ['captain_id' => $captain->id]);
    }

    public function public(): static
    {
        return $this->state(fn () => ['is_public' => true]);
    }

    public function private(): static
    {
        return $this->state(fn () => ['is_public' => false]);
    }

    public function withMembers(int $count): static
    {
        return $this->afterCreating(function (Team $team) use ($count): void {
            for ($i = 0; $i < $count; $i++) {
                $user = User::factory()->create();
                TeamMember::create([
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                    'role' => 'member',
                    'status' => 'active',
                    'joined_at' => now(),
                ]);
            }

            $team->increment('total_members', $count);
        });
    }
}
