<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamMember>
 */
class TeamMemberFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
        ];
    }

    public function asMember(): static
    {
        return $this->state(fn () => ['role' => 'member']);
    }

    public function asAdmin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }

    public function asCaptain(): static
    {
        return $this->state(fn () => ['role' => 'captain']);
    }

    public function invited(): static
    {
        return $this->state(fn () => ['status' => 'invited', 'joined_at' => null]);
    }

    public function left(): static
    {
        return $this->state(fn () => ['status' => 'left', 'left_at' => now()]);
    }
}
