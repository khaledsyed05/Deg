<?php

namespace App\Services\Profile;

use App\Models\User;

class AchievementsService
{
    /**
     * Return the canonical achievements payload for a user.
     *
     * @return array{achievements: array<int, array<string, mixed>>, total_points: int}
     */
    public function achievementsArray(User $user): array
    {
        $achievements = $user->achievements;

        $totalPoints = $achievements
            ->filter(fn ($a) => $a->isUnlocked())
            ->sum(fn ($a) => $a->getMetadata()['points'] ?? 0);

        return [
            'achievements' => $achievements->map(fn ($achievement) => [
                'type' => $achievement->type->value,
                'metadata' => $achievement->getMetadata(),
                'progress' => (int) $achievement->progress,
                'target' => (int) $achievement->target,
                'unlocked' => $achievement->isUnlocked(),
                'unlocked_at' => $achievement->unlocked_at?->toIso8601String(),
            ])->values()->all(),
            'total_points' => (int) $totalPoints,
        ];
    }
}
