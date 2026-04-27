<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\PlayerStats;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlayerController extends Controller
{
    use ApiResponse;

    public function stats(): JsonResponse
    {
        $user = auth()->user();
        $stats = PlayerStats::firstOrCreate(['user_id' => $user->id]);
        $stats->load(['favoriteSport', 'favoriteVenue']);

        return $this->success([
            'total_bookings' => $stats->total_bookings,
            'completed_bookings' => $stats->completed_bookings,
            'cancelled_bookings' => $stats->cancelled_bookings,
            'total_hours_played' => $stats->total_hours_played,
            'total_spent' => $stats->total_spent,
            'favorite_sport' => $stats->favoriteSport?->name,
            'favorite_venue' => $stats->favoriteVenue?->name,
            'average_rating_given' => $stats->average_rating_given,
            'current_streak' => $stats->streak_days,
            'bookings_this_month' => $stats->bookings_this_month,
        ]);
    }

    public function achievements(): JsonResponse
    {
        $achievements = auth()->user()->achievements;

        $totalPoints = $achievements
            ->filter(fn ($a) => $a->isUnlocked())
            ->sum(fn ($a) => $a->getMetadata()['points']);

        return $this->success([
            'achievements' => $achievements->map(fn ($achievement) => [
                'type' => $achievement->type->value,
                'metadata' => $achievement->getMetadata(),
                'progress' => $achievement->progress,
                'target' => $achievement->target,
                'unlocked' => $achievement->isUnlocked(),
                'unlocked_at' => $achievement->unlocked_at?->toIso8601String(),
            ])->values(),
            'total_points' => $totalPoints,
        ]);
    }

    public function history(): JsonResponse
    {
        $bookings = auth()->user()->bookings()
            ->with(['venue', 'payments'])
            ->latest()
            ->paginate(20);

        return response()->json($bookings);
    }

    public function updateBio(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bio' => 'nullable|string|max:500',
            'interests' => 'nullable|array|max:10',
            'interests.*' => 'string|max:50',
        ]);

        auth()->user()->forceFill([
            'bio' => $validated['bio'] ?? null,
            'interests' => $validated['interests'] ?? null,
        ])->save();

        return $this->success(null, 'تم تحديث البروفايل');
    }

    public function show(int $id): JsonResponse
    {
        $user = User::with(['playerStats.favoriteSport', 'achievements'])->findOrFail($id);
        $privacy = $user->getPrivacySettings();

        if ($privacy['profile_visibility'] === 'private' && $id !== auth()->id()) {
            return $this->error('هذا الملف الشخصي خاص', null, 403);
        }

        $stats = $user->playerStats;

        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'avatar_url' => $user->getAvatarUrl(),
            'bio' => $user->bio,
            'interests' => $user->interests,
            'stats' => [
                'total_bookings' => $privacy['show_bookings'] ? $stats?->total_bookings : null,
                'total_hours_played' => $stats?->total_hours_played,
                'favorite_sport' => $stats?->favoriteSport?->name,
            ],
            'achievements' => $user->achievements->whereNotNull('unlocked_at')->count(),
        ]);
    }

    public function leaderboard(Request $request): JsonResponse
    {
        $allowed = ['total_bookings', 'total_hours_played', 'total_spent'];
        $metric = $request->get('metric', 'total_bookings');

        if (! in_array($metric, $allowed, true)) {
            $metric = 'total_bookings';
        }

        $stats = PlayerStats::query()
            ->with('user')
            ->orderByDesc($metric)
            ->limit(100)
            ->get();

        return $this->success([
            'metric' => $metric,
            'leaderboard' => $stats->values()->map(fn ($stat, $index) => [
                'rank' => $index + 1,
                'user' => [
                    'id' => $stat->user->id,
                    'name' => $stat->user->name,
                    'avatar_url' => $stat->user->getAvatarUrl(),
                ],
                'value' => $stat->{$metric},
            ]),
        ]);
    }
}
