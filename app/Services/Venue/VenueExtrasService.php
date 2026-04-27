<?php

namespace App\Services\Venue;

use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VenueExtrasService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPopularVenues(?int $cityId = null, int $limit = 20): array
    {
        $cacheKey = 'popular_venues_'.($cityId ?? 'all')."_{$limit}";

        return Cache::remember($cacheKey, 3600, function () use ($cityId, $limit) {
            $query = Venue::query()
                ->where('status', 'active')
                ->withCount(['bookings' => fn ($q) => $q->where('created_at', '>=', now()->subDays(30))])
                ->orderByDesc('bookings_count');

            if ($cityId) {
                $query->whereHas('club', fn ($q) => $q->where('city_id', $cityId));
            }

            return $query->limit($limit)->get()->toArray();
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRecentlyViewed(User $user, int $limit = 20): array
    {
        return DB::table('venue_views')
            ->where('venue_views.user_id', $user->id)
            ->join('venues', 'venues.id', '=', 'venue_views.venue_id')
            ->where('venues.status', 'active')
            ->select('venues.*', DB::raw('MAX(venue_views.viewed_at) as last_viewed_at'))
            ->groupBy('venues.id')
            ->orderByDesc('last_viewed_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getSimilarVenues(string $slug, int $limit = 10): array
    {
        $venue = Venue::where('slug', $slug)->firstOrFail();

        return Cache::remember("similar_venues_{$venue->id}_{$limit}", 3600, function () use ($venue, $limit) {
            return Venue::query()
                ->where('id', '!=', $venue->id)
                ->where('status', 'active')
                ->where(function ($q) use ($venue) {
                    $q->where('category_id', $venue->category_id)
                        ->orWhere('club_id', $venue->club_id);
                })
                ->withCount(['bookings'])
                ->orderByDesc('bookings_count')
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function getVenuePhotos(string $slug): array
    {
        $venue = Venue::where('slug', $slug)->firstOrFail();

        $photos = $venue->getMedia('photos')->map(fn ($m) => [
            'id' => $m->id,
            'url' => $m->getUrl(),
            'thumbnail_url' => method_exists($m, 'getUrl') ? $m->getUrl() : null,
            'order' => (int) ($m->order_column ?? 0),
            'name' => $m->name,
        ])->values();

        return [
            'venue_id' => $venue->id,
            'venue_slug' => $venue->slug,
            'venue_name_ar' => $venue->getTranslation('name', 'ar', false),
            'photos' => $photos->toArray(),
            'total' => $photos->count(),
        ];
    }
}
