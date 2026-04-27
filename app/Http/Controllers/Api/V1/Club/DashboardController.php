<?php

namespace App\Http\Controllers\Api\V1\Club;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Booking;
use App\Models\Club;
use App\Models\Event;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    use ApiResponse;

    public function stats(Request $request): JsonResponse
    {
        $clubId = (int) $request->attributes->get('club_id');
        $club = Club::findOrFail($clubId);
        $venueIds = $club->venues()->pluck('id');

        $bookings = fn () => Booking::whereIn('venue_id', $venueIds);
        $reviews = fn () => Review::whereIn('venue_id', $venueIds)->where('is_published', true);

        return $this->success([
            'club_name' => $club->getTranslation('name', 'ar', false) ?: $club->getTranslation('name', 'en', false),
            'total_venues' => $venueIds->count(),
            'today_bookings' => $bookings()->whereDate('booking_date', today())->count(),
            'week_bookings' => $bookings()->where('booking_date', '>=', now()->startOfWeek())->count(),
            'today_revenue' => (int) $bookings()->whereDate('booking_date', today())
                ->whereIn('status', ['confirmed', 'completed'])->sum('total_price'),
            'month_revenue' => (int) $bookings()->where('booking_date', '>=', now()->startOfMonth())
                ->whereIn('status', ['confirmed', 'completed'])->sum('total_price'),
            'pending_bookings' => $bookings()->where('status', 'pending_payment')->count(),
            'avg_rating' => round((float) $reviews()->avg('rating'), 2),
            'total_reviews' => $reviews()->count(),
            'followers' => (int) ($club->followers_count ?? 0),
            'upcoming_events' => Event::where('club_id', $clubId)->upcoming()->count(),
        ]);
    }

    public function bookingsTrend(Request $request): JsonResponse
    {
        $clubId = (int) $request->attributes->get('club_id');
        $venueIds = Club::findOrFail($clubId)->venues()->pluck('id');
        $days = min(180, max(1, $request->integer('days', 30)));

        $rows = Booking::whereIn('venue_id', $venueIds)
            ->where('booking_date', '>=', now()->subDays($days))
            ->selectRaw('booking_date, COUNT(*) as bookings, SUM(total_price) as revenue')
            ->groupBy('booking_date')
            ->orderBy('booking_date')
            ->get();

        return $this->success($rows->toArray());
    }

    public function topVenues(Request $request): JsonResponse
    {
        $clubId = (int) $request->attributes->get('club_id');
        $club = Club::findOrFail($clubId);

        $venues = $club->venues()
            ->withCount(['bookings' => fn ($q) => $q->where('created_at', '>=', now()->subDays(30))])
            ->orderByDesc('bookings_count')
            ->limit(10)
            ->get();

        return $this->success($venues->toArray());
    }

    public function recentActivity(Request $request): JsonResponse
    {
        $clubId = (int) $request->attributes->get('club_id');
        $venueIds = Club::findOrFail($clubId)->venues()->pluck('id');
        $limit = min(50, max(1, $request->integer('limit', 20)));

        $bookings = Booking::whereIn('venue_id', $venueIds)
            ->with(['user:id,name', 'venue:id,name'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($b) => [
                'type' => 'booking',
                'created_at' => $b->created_at?->toIso8601String(),
                'user_name' => $b->user?->name,
                'venue_name' => $b->venue?->getTranslation('name', 'ar', false),
                'status' => $b->status instanceof \BackedEnum ? $b->status->value : $b->status,
                'amount' => (int) $b->total_price,
            ]);

        $reviews = Review::whereIn('venue_id', $venueIds)
            ->where('is_published', true)
            ->with(['user:id,name', 'venue:id,name'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'type' => 'review',
                'created_at' => $r->created_at?->toIso8601String(),
                'user_name' => $r->user?->name,
                'venue_name' => $r->venue?->getTranslation('name', 'ar', false),
                'rating' => (float) $r->rating,
                'comment_preview' => Str::limit($r->comment ?? '', 100),
            ]);

        $merged = $bookings->merge($reviews)
            ->sortByDesc('created_at')
            ->take($limit)
            ->values();

        return $this->success($merged->toArray());
    }
}
