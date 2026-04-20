<?php

namespace Database\Factories;

use App\Models\CommissionConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommissionConfig>
 */
class CommissionConfigFactory extends Factory
{
    public function definition(): array
    {
        return [
            'scope' => 'global',
            'commission_type' => 'percentage',
            'commission_value' => 700, // 7% in basis points
            'is_active' => true,
            'effective_from' => now()->subYear(),
        ];
    }

    public function forClub(int $clubId, int $basisPoints = 500): static
    {
        return $this->state(fn () => [
            'scope' => 'club',
            'club_id' => $clubId,
            'commission_value' => $basisPoints,
        ]);
    }
}
