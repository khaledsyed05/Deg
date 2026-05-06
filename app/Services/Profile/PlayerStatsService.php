<?php

namespace App\Services\Profile;

use App\Models\PlayerStats;
use App\Models\User;

class PlayerStatsService
{
    /**
     * Return the canonical stats payload for a user.
     *
     * Shared by PlayerController@stats (Sprint 1) and
     * SportsProfileController@me (Sprint 5) so both wire formats
     * stay in lock-step.
     *
     * @return array<string, mixed>
     */
    public function statsArray(User $user): array
    {
        $stats = PlayerStats::firstOrCreate(['user_id' => $user->id]);
        $stats->load(['favoriteSport', 'favoriteVenue']);

        return [
            'total_bookings' => (int) $stats->total_bookings,
            'completed_bookings' => (int) $stats->completed_bookings,
            'cancelled_bookings' => (int) $stats->cancelled_bookings,
            'total_hours_played' => (int) $stats->total_hours_played,
            'total_spent' => (int) $stats->total_spent,
            'favorite_sport' => $stats->favoriteSport?->name,
            'favorite_venue' => $stats->favoriteVenue?->name,
            'average_rating_given' => $stats->average_rating_given,
            'current_streak' => (int) $stats->streak_days,
            'bookings_this_month' => (int) $stats->bookings_this_month,
        ];
    }
}
