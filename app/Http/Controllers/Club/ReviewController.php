<?php

namespace App\Http\Controllers\Club;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\Review;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $filters = [
            'search' => $request->string('search')->toString(),
            'rating' => $request->string('rating')->toString(),
            'venue_slug' => $request->string('venue_slug')->toString(),
            'status' => $request->string('status')->toString(),
            'reply_status' => $request->string('reply_status')->toString(),
            'sort' => $request->string('sort')->toString() ?: 'recent',
        ];

        $locale = app()->getLocale();

        // Stats — reviews have club_id directly, no need to traverse booking→venue.
        $totalReviews = (int) Review::where('club_id', $club->id)->count();
        $avgRating = (float) (Review::where('club_id', $club->id)->avg('rating') ?? 0);

        $thisMonth = (int) Review::where('club_id', $club->id)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        $pendingReplies = (int) Review::where('club_id', $club->id)
            ->whereNull('club_reply')
            ->count();

        // Rating breakdown — bucket decimals (e.g. 4.5) into integer stars.
        $breakdown = Review::query()
            ->where('club_id', $club->id)
            ->selectRaw('ROUND(rating) as star, COUNT(*) as cnt')
            ->groupBy('star')
            ->pluck('cnt', 'star')
            ->all();

        $ratingStats = [];
        for ($i = 5; $i >= 1; $i--) {
            $count = (int) ($breakdown[$i] ?? 0);
            $ratingStats[] = [
                'rating' => $i,
                'count' => $count,
                'percentage' => $totalReviews > 0 ? (int) round(($count / $totalReviews) * 100) : 0,
            ];
        }

        // Reviews list
        $query = Review::query()
            ->where('club_id', $club->id)
            ->with([
                'user:id,name',
                'booking:id,booking_code,booking_date,venue_id',
                'booking.venue:id,slug,name',
            ]);

        if ($search = $filters['search']) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('body', 'like', "%{$search}%")
                    ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        if ($filters['rating'] !== '') {
            $rating = (int) $filters['rating'];
            if ($rating >= 1 && $rating <= 5) {
                $query->whereRaw('ROUND(rating) = ?', [$rating]);
            }
        }

        if ($venueSlug = $filters['venue_slug']) {
            $venueId = Venue::where('club_id', $club->id)->where('slug', $venueSlug)->value('id');
            if ($venueId) {
                $query->whereHas('booking', fn (Builder $b) => $b->where('venue_id', $venueId));
            }
        }

        if ($filters['status'] === 'visible') {
            $query->where('is_published', true);
        } elseif ($filters['status'] === 'hidden') {
            $query->where('is_published', false);
        }

        if ($filters['reply_status'] === 'replied') {
            $query->whereNotNull('club_reply');
        } elseif ($filters['reply_status'] === 'pending') {
            $query->whereNull('club_reply');
        }

        match ($filters['sort']) {
            'highest' => $query->orderByDesc('rating')->latest('created_at'),
            'lowest' => $query->orderBy('rating')->latest('created_at'),
            default => $query->latest('created_at'),
        };

        $reviews = $query
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Review $r) => [
                'id' => $r->id,
                'rating' => (float) $r->rating,
                'comment' => $r->body,
                'status' => $r->is_published ? 'visible' : 'hidden',
                'has_reply' => ! is_null($r->club_reply),
                'created_at' => $r->created_at?->toIso8601String(),
                'player' => [
                    'id' => $r->user?->id,
                    'name' => $r->is_anonymous ? '—' : ($r->user?->name ?? '—'),
                    'anonymous' => (bool) $r->is_anonymous,
                ],
                'venue' => [
                    'slug' => $r->booking?->venue?->slug,
                    'name' => $r->booking?->venue?->getTranslation('name', $locale)
                        ?: $r->booking?->venue?->getTranslation('name', 'ar')
                        ?: '—',
                ],
                'booking' => [
                    'code' => $r->booking?->booking_code,
                    'date' => $r->booking?->booking_date?->toDateString(),
                ],
            ]);

        return Inertia::render('Club/Reviews/Index', [
            'stats' => [
                'avg_rating' => round($avgRating, 1),
                'total_reviews' => $totalReviews,
                'this_month' => $thisMonth,
                'pending_replies' => $pendingReplies,
            ],
            'ratingStats' => $ratingStats,
            'reviews' => $reviews,
            'filters' => $filters,
            'venues' => $club->venues()
                ->orderBy('name->ar')
                ->get(['id', 'slug', 'name'])
                ->map(fn (Venue $v) => [
                    'slug' => $v->slug,
                    'name' => $v->getTranslation('name', $locale) ?: $v->getTranslation('name', 'ar'),
                ]),
        ]);
    }

    public function show(Review $review): Response|RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $this->ensureOwnership($review, $club);

        $locale = app()->getLocale();

        $review->load([
            'user:id,name',
            'booking:id,booking_code,booking_date,total_price,status,venue_id',
            'booking.venue:id,slug,name',
            'clubRepliedBy:id,name',
        ]);

        return Inertia::render('Club/Reviews/Show', [
            'review' => [
                'id' => $review->id,
                'rating' => (float) $review->rating,
                'body' => $review->body,
                'is_published' => (bool) $review->is_published,
                'is_hidden' => ! (bool) $review->is_published,
                'created_at' => $review->created_at?->toIso8601String(),
                'player' => ($review->is_anonymous || ! $review->user) ? null : [
                    'id' => $review->user->id,
                    'name' => $review->user->name,
                ],
                'venue' => [
                    'slug' => $review->booking?->venue?->slug,
                    'name' => $review->booking?->venue?->getTranslation('name', $locale)
                        ?: $review->booking?->venue?->getTranslation('name', 'ar')
                        ?: '—',
                ],
                'booking' => [
                    'id' => $review->booking?->id,
                    'code' => $review->booking?->booking_code,
                    'date' => $review->booking?->booking_date?->toDateString(),
                    'price' => (int) ($review->booking?->total_price ?? 0),
                    'status' => $review->booking?->status instanceof BookingStatus
                        ? $review->booking->status->value
                        : $review->booking?->status,
                ],
                'club_reply' => $review->club_reply,
                'club_replied_at' => $review->club_replied_at?->toIso8601String(),
                'club_replied_by' => $review->clubRepliedBy?->name,
            ],
        ]);
    }

    public function storeReply(Request $request, Review $review): RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $this->ensureOwnership($review, $club);

        $validated = $request->validate([
            'reply' => ['required', 'string', 'max:500'],
        ]);

        $review->update([
            'club_reply' => $validated['reply'],
            'club_replied_at' => now(),
            'club_replied_by' => Auth::id(),
        ]);

        activity()
            ->performedOn($review)
            ->causedBy(Auth::user())
            ->log('club_review_replied');

        return back()
            ->with('flash_key', 'replyAdded')
            ->with('flash_type', 'success');
    }

    public function updateReply(Request $request, Review $review): RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $this->ensureOwnership($review, $club);

        $validated = $request->validate([
            'reply' => ['required', 'string', 'max:500'],
        ]);

        $review->update([
            'club_reply' => $validated['reply'],
            'club_replied_by' => Auth::id(),
        ]);

        activity()
            ->performedOn($review)
            ->causedBy(Auth::user())
            ->log('club_review_reply_updated');

        return back()
            ->with('flash_key', 'replyUpdated')
            ->with('flash_type', 'success');
    }

    public function destroyReply(Review $review): RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $this->ensureOwnership($review, $club);

        $review->update([
            'club_reply' => null,
            'club_replied_at' => null,
            'club_replied_by' => null,
        ]);

        activity()
            ->performedOn($review)
            ->causedBy(Auth::user())
            ->log('club_review_reply_deleted');

        return back()
            ->with('flash_key', 'replyDeleted')
            ->with('flash_type', 'success');
    }

    public function toggleStatus(Review $review): RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $this->ensureOwnership($review, $club);

        $makeVisible = ! $review->is_published;

        $review->update([
            'is_published' => $makeVisible,
            'published_at' => $makeVisible ? ($review->published_at ?? now()) : $review->published_at,
            'hidden_at' => $makeVisible ? null : now(),
            'hidden_by' => $makeVisible ? null : Auth::id(),
        ]);

        activity()
            ->performedOn($review)
            ->causedBy(Auth::user())
            ->log($makeVisible ? 'club_review_shown' : 'club_review_hidden');

        return back()
            ->with('flash_key', $makeVisible ? 'reviewShown' : 'reviewHidden')
            ->with('flash_type', 'success');
    }

    private function ensureOwnership(Review $review, Club $club): void
    {
        if ((int) $review->club_id !== (int) $club->id) {
            abort(403, 'غير مخوّل للوصول إلى هذا التقييم.');
        }
    }

    private function resolveClub(): Club|RedirectResponse
    {
        $user = Auth::user();

        $club = Club::where('owner_id', $user->id)->first()
            ?? $user->clubs()->first();

        if (! $club) {
            return redirect()
                ->route('club.dashboard')
                ->with('error', 'لا يوجد نادٍ مرتبط بحسابك.');
        }

        return $club;
    }
}
