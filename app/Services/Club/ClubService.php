<?php

namespace App\Services\Club;

use App\Exceptions\Club\AlreadyFollowingException;
use App\Exceptions\Club\NotFollowingException;
use App\Models\Club;
use App\Models\ClubFollower;
use App\Models\ClubUpdate;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ClubService
{
    public function getClubDetails(int $clubId, ?User $user = null): array
    {
        $club = Club::with(['city'])
            ->withCount(['venues', 'followers'])
            ->findOrFail($clubId);

        $logoUrl = $club->getFirstMediaUrl('logo') ?: null;
        $coverUrl = $club->getFirstMediaUrl('cover') ?: null;

        return [
            'id' => $club->id,
            'slug' => $club->slug,
            'name' => $club->getTranslation('name', 'en', false),
            'name_ar' => $club->getTranslation('name', 'ar', false),
            'description' => $club->getTranslation('description', 'en', false),
            'description_ar' => $club->getTranslation('description', 'ar', false),
            'logo_url' => $logoUrl,
            'cover_image_url' => $coverUrl,
            'city' => $club->city ? [
                'id' => $club->city->id,
                'name_ar' => $club->city->name_ar,
            ] : null,
            'address' => $club->address,
            'venues_count' => (int) $club->venues_count,
            'followers_count' => (int) ($club->followers_count ?? 0),
            'rating' => (float) ($club->avg_rating ?? 0),
            'reviews_count' => (int) ($club->reviews_count ?? 0),
            'is_featured' => (bool) $club->is_featured,
            'is_followed' => $user ? $club->isFollowedBy($user) : false,
            'location' => $club->latitude && $club->longitude ? [
                'latitude' => (float) $club->latitude,
                'longitude' => (float) $club->longitude,
            ] : null,
        ];
    }

    public function getClubVenues(int $clubId, int $perPage = 15): array
    {
        $club = Club::findOrFail($clubId);

        $venues = $club->venues()
            ->with(['category'])
            ->withCount(['reviews'])
            ->paginate($perPage);

        return [
            'data' => $venues->items(),
            'meta' => [
                'current_page' => $venues->currentPage(),
                'last_page' => $venues->lastPage(),
                'per_page' => $venues->perPage(),
                'total' => $venues->total(),
            ],
        ];
    }

    public function getClubReviews(int $clubId, int $perPage = 15): array
    {
        $club = Club::findOrFail($clubId);
        $venueIds = $club->venues()->pluck('id');

        $query = Review::whereIn('venue_id', $venueIds)
            ->where('is_published', true);

        $reviews = (clone $query)
            ->with(['user:id,name,avatar_url', 'venue:id,name,slug'])
            ->latest()
            ->paginate($perPage);

        $stats = (clone $query)
            ->selectRaw('
                COUNT(*) as total,
                AVG(rating) as average_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as stars_5,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as stars_4,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as stars_3,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as stars_2,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as stars_1
            ')
            ->first();

        return [
            'data' => $reviews->items(),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'total' => $reviews->total(),
            ],
            'stats' => [
                'average_rating' => round((float) ($stats->average_rating ?? 0), 2),
                'total_reviews' => (int) ($stats->total ?? 0),
                'distribution' => [
                    '5' => (int) ($stats->stars_5 ?? 0),
                    '4' => (int) ($stats->stars_4 ?? 0),
                    '3' => (int) ($stats->stars_3 ?? 0),
                    '2' => (int) ($stats->stars_2 ?? 0),
                    '1' => (int) ($stats->stars_1 ?? 0),
                ],
            ],
        ];
    }

    public function getClubContact(int $clubId): array
    {
        $club = Club::findOrFail($clubId);

        return [
            'phone' => $club->phone_number,
            'whatsapp' => $club->phone_number,
            'address' => $club->address,
            'location' => $club->latitude && $club->longitude ? [
                'latitude' => (float) $club->latitude,
                'longitude' => (float) $club->longitude,
            ] : null,
        ];
    }

    public function followClub(
        int $clubId,
        User $user,
        bool $notifyUpdates = true,
        bool $notifyEvents = true,
        bool $notifyPromotions = true
    ): ClubFollower {
        $club = Club::findOrFail($clubId);

        if ($club->isFollowedBy($user)) {
            throw new AlreadyFollowingException('أنت تتابع هذا النادي بالفعل');
        }

        return DB::transaction(function () use ($club, $user, $notifyUpdates, $notifyEvents, $notifyPromotions) {
            $follower = ClubFollower::create([
                'user_id' => $user->id,
                'club_id' => $club->id,
                'notify_updates' => $notifyUpdates,
                'notify_events' => $notifyEvents,
                'notify_promotions' => $notifyPromotions,
            ]);

            $club->increment('followers_count');

            return $follower;
        });
    }

    public function unfollowClub(int $clubId, User $user): void
    {
        $club = Club::findOrFail($clubId);

        $follower = ClubFollower::where('user_id', $user->id)
            ->where('club_id', $clubId)
            ->first();

        if (! $follower) {
            throw new NotFollowingException('أنت لا تتابع هذا النادي');
        }

        DB::transaction(function () use ($follower, $club) {
            $follower->delete();
            $club->decrement('followers_count');
        });
    }

    public function getFollowedClubs(User $user, int $perPage = 15): array
    {
        $clubs = $user->followedClubs()
            ->withCount(['venues', 'followers'])
            ->with('city:id,name_ar')
            ->orderByDesc('club_followers.created_at')
            ->paginate($perPage);

        return [
            'data' => $clubs->items(),
            'meta' => [
                'current_page' => $clubs->currentPage(),
                'last_page' => $clubs->lastPage(),
                'total' => $clubs->total(),
            ],
        ];
    }

    public function getClubFeed(int $clubId, int $perPage = 15, ?string $type = null): array
    {
        $query = ClubUpdate::where('club_id', $clubId)
            ->published()
            ->with(['event:id,title_ar,starts_at', 'promotion:id,name'])
            ->recent();

        if ($type) {
            $query->where('type', $type);
        }

        $updates = $query->paginate($perPage);

        return [
            'data' => $updates->items(),
            'meta' => [
                'current_page' => $updates->currentPage(),
                'last_page' => $updates->lastPage(),
                'total' => $updates->total(),
            ],
        ];
    }
}
