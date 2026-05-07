<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Moderation\ModerationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Venue\ByBoundsRequest;
use App\Http\Requests\Api\V1\Venue\GetAvailableSlotsRequest;
use App\Http\Requests\Api\V1\Venue\ListVenuesRequest;
use App\Http\Requests\Api\V1\Venue\NearbyVenuesRequest;
use App\Http\Requests\Api\V1\Venue\ReportVenueRequest;
use App\Http\Requests\Api\V1\Venue\SearchVenuesRequest;
use App\Http\Resources\V1\Venue\VenueDetailResource;
use App\Http\Resources\Venue\VenueMapResource;
use App\Http\Resources\VenueResource;
use App\Http\Traits\ApiResponse;
use App\Models\Venue;
use App\Repositories\Contracts\VenueRepositoryInterface;
use App\Services\Booking\SlotAvailabilityService;
use App\Services\Moderation\VenueReportService;
use App\Services\Venue\VenueExtrasService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    use ApiResponse;

    private const EAGER_LIST = ['category', 'club.city', 'media'];

    public function __construct(
        private VenueRepositoryInterface $venueRepo,
        private SlotAvailabilityService $slotAvailabilityService,
    ) {}

    public function index(ListVenuesRequest $request): JsonResponse
    {
        $query = $this->baseListQuery();

        if ($request->integer('city_id')) {
            $query->inCity($request->integer('city_id'));
        }

        if ($request->integer('category_id')) {
            $query->withCategory($request->integer('category_id'));
        }

        if ($request->has('min_price') || $request->has('max_price')) {
            $query->priceRange($request->integer('min_price') ?: null, $request->integer('max_price') ?: null);
        }

        if ($request->boolean('is_featured')) {
            $query->featured();
        }

        $this->applySort($query, $request->input('sort_by'));

        return $this->paginated(
            $query->paginate($request->integer('per_page') ?: 15),
            VenueResource::class,
        );
    }

    public function search(SearchVenuesRequest $request): JsonResponse
    {
        $query = $this->baseListQuery()->searchTranslated((string) $request->input('query'));

        if ($request->integer('city_id')) {
            $query->inCity($request->integer('city_id'));
        }

        if ($request->integer('category_id')) {
            $query->withCategory($request->integer('category_id'));
        }

        return $this->paginated(
            $query->paginate($request->integer('per_page') ?: 15),
            VenueResource::class,
        );
    }

    /**
     * Lightweight venue list inside a lat/lng bounding box. Tuned for
     * map viewports — returns {@see VenueMapResource}, not the full
     * venue resource, so a 200-marker viewport paints fast on mobile.
     */
    public function byBounds(ByBoundsRequest $request): JsonResponse
    {
        $categoryId = $request->integer('category_id') ?: $request->integer('sport_id');
        $limit = $request->integer('limit') ?: 200;

        $venues = $this->venueRepo->query()
            ->active()
            ->with(['club.city'])
            ->withinBounds(
                north: (float) $request->validated('north'),
                south: (float) $request->validated('south'),
                east: (float) $request->validated('east'),
                west: (float) $request->validated('west'),
            )
            ->when($categoryId, fn ($q, $id) => $q->where('category_id', $id))
            ->limit($limit)
            ->get();

        return $this->success(VenueMapResource::collection($venues));
    }

    public function nearby(NearbyVenuesRequest $request): JsonResponse
    {
        $query = $this->baseListQuery()->nearby(
            (float) $request->input('latitude'),
            (float) $request->input('longitude'),
            (float) ($request->integer('radius_km') ?: 10),
        );

        if ($request->integer('category_id')) {
            $query->withCategory($request->integer('category_id'));
        }

        return $this->paginated(
            $query->paginate($request->integer('per_page') ?: 15),
            VenueResource::class,
        );
    }

    public function featured(): JsonResponse
    {
        $venues = $this->baseListQuery()
            ->featured()
            ->orderByDesc('view_count')
            ->limit(10)
            ->get();

        return $this->success(VenueResource::collection($venues));
    }

    public function popular(Request $request, VenueExtrasService $service): JsonResponse
    {
        $cityId = $request->integer('city_id') ?: null;
        $limit = min(50, max(1, $request->integer('limit', 20)));

        return $this->success($service->getPopularVenues($cityId, $limit));
    }

    public function recentlyViewed(Request $request, VenueExtrasService $service): JsonResponse
    {
        $limit = min(50, max(1, $request->integer('limit', 20)));

        return $this->success($service->getRecentlyViewed($request->user(), $limit));
    }

    public function similar(string $slug, Request $request, VenueExtrasService $service): JsonResponse
    {
        $limit = min(20, max(1, $request->integer('limit', 10)));

        return $this->success($service->getSimilarVenues($slug, $limit));
    }

    public function photos(string $slug, VenueExtrasService $service): JsonResponse
    {
        return $this->success($service->getVenuePhotos($slug));
    }

    public function show(Venue $venue): JsonResponse
    {
        $venue->load(['category', 'club.city', 'media']);
        $venue->recordView(request()->user(), request()->ip());

        return $this->success(new VenueDetailResource($venue));
    }

    public function availability(Venue $venue): JsonResponse
    {
        $venue->load('club');
        $settings = $venue->club?->settings ?? [];

        return $this->success([
            'venue_id' => $venue->id,
            'slug' => $venue->slug,
            'opening_hours' => $venue->opening_hours ?? [],
            'business_hours' => $settings['business_hours'] ?? null,
            'booking_rules' => $settings['booking_rules'] ?? null,
        ]);
    }

    public function reviews(Venue $venue): JsonResponse
    {
        $reviews = $venue->club?->reviews()
            ->with('user')
            ->whereHas('booking', fn (Builder $q) => $q->where('venue_id', $venue->id))
            ->where('is_published', true)
            ->latest()
            ->paginate(20);

        if (! $reviews) {
            return $this->success([
                'reviews' => [],
                'rating_summary' => ['average' => 0, 'total' => 0, 'distribution' => []],
            ]);
        }

        return $this->success([
            'reviews' => $reviews->items(),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
            'rating_summary' => [
                'average' => (float) ($venue->avg_rating ?? 0),
                'total' => (int) ($venue->reviews_count ?? 0),
            ],
        ]);
    }

    public function availableSlots(GetAvailableSlotsRequest $request, Venue $venue): JsonResponse
    {
        $result = $this->slotAvailabilityService->check(
            venueId: $venue->id,
            date: $request->date,
            startTime: '00:00',
            durationMinutes: $request->duration_minutes,
        );

        return $this->success([
            'available' => $result->available,
            'unavailable_reason' => $result->unavailableReason,
        ]);
    }

    private function baseListQuery(): Builder
    {
        return $this->venueRepo->query()
            ->active()
            ->with(self::EAGER_LIST);
    }

    private function applySort(Builder $query, ?string $sortBy): void
    {
        match ($sortBy) {
            'price_asc' => $query->orderBy('price_from'),
            'price_desc' => $query->orderByDesc('price_from'),
            'rating' => $query->orderByDesc('avg_rating'),
            'popular' => $query->orderByDesc('view_count'),
            'newest' => $query->latest(),
            default => $query->orderByDesc('is_featured')->latest(),
        };
    }

    public function report(string $slug, ReportVenueRequest $request, VenueReportService $service): JsonResponse
    {
        $venue = Venue::where('slug', $slug)->firstOrFail();

        try {
            $report = $service->report(
                $venue,
                $request->user(),
                $request->validated('reason'),
                $request->validated('description'),
                $request->validated('evidence_urls', []) ?? [],
            );
        } catch (ModerationException $e) {
            return $this->error($e->getMessage(), null, $e->statusCode);
        }

        return $this->success([
            'report_id' => $report->id,
            'status' => $report->status,
        ], 'تم استلام التقرير، سنراجعه قريباً', 201);
    }
}
