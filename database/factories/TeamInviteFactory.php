<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\TeamInvite;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TeamInvite>
 */
class TeamInviteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'code' => Str::upper(Str::random(8)),
            'created_by' => User::factory(),
            'expires_at' => null,
            'max_uses' => null,
            'uses_count' => 0,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subDay()]);
    }

    public function usedUp(): static
    {
        return $this->state(fn () => ['max_uses' => 1, 'uses_count' => 1]);
    }
}
