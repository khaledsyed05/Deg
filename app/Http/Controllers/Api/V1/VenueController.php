<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Venue\GetAvailableSlotsRequest;
use App\Http\Requests\Api\V1\Venue\ListVenuesRequest;
use App\Http\Requests\Api\V1\Venue\SearchVenuesRequest;
use App\Http\Resources\VenueResource;
use App\Models\Venue;
use App\Repositories\Contracts\VenueRepositoryInterface;
use App\Services\Booking\SlotAvailabilityService;
use Illuminate\Http\JsonResponse;

class VenueController extends Controller
{
    public function __construct(
        private VenueRepositoryInterface $venueRepo,
        private SlotAvailabilityService $slotAvailabilityService,
    ) {}

    /**
     * List venues
     *
     * Returns a paginated list of active venues. Filterable by city and category.
     *
     * @unauthenticated
     *
     * @queryParam city_id integer Filter by city ID. Example: 1
     * @queryParam category_id integer Filter by sport category ID. Example: 2
     * @queryParam per_page integer Results per page (default: 15, max: 100). Example: 20
     *
     * @response 200 {"success": true, "data": [...], "meta": {"current_page": 1, "last_page": 3, "per_page": 15, "total": 42}}
     */
    public function index(ListVenuesRequest $request): JsonResponse
    {
        $query = $this->venueRepo->query()->active()->with(['club']);

        if ($request->city_id) {
            $query->where('city_id', $request->city_id);
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $venues = $query->orderByDesc('id')->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => VenueResource::collection($venues),
            'meta' => [
                'current_page' => $venues->currentPage(),
                'last_page' => $venues->lastPage(),
                'per_page' => $venues->perPage(),
                'total' => $venues->total(),
            ],
        ]);
    }

    /**
     * Get venue details
     *
     * Returns full details for a single venue including club information.
     *
     * @unauthenticated
     *
     * @response 200 {"success": true, "data": {"id": 1, "name": {"ar": "ملعب النور", "en": "Al-Nour Field"}}}
     * @response 404 {"message": "Not Found"}
     */
    public function show(Venue $venue): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new VenueResource($venue->load(['club'])),
        ]);
    }

    /**
     * Search venues
     *
     * Full-text search across venue names (Arabic and English).
     *
     * @unauthenticated
     *
     * @queryParam query string required Search term. Example: ملعب
     * @queryParam city_id integer Filter results by city. Example: 1
     * @queryParam per_page integer Results per page. Example: 15
     *
     * @response 200 {"success": true, "data": [...], "meta": {"total": 5}}
     */
    public function search(SearchVenuesRequest $request): JsonResponse
    {
        $searchQuery = $request->input('query');
        $query = $this->venueRepo->query()
            ->active()
            ->where(function ($q) use ($searchQuery) {
                $q->whereRaw("JSON_EXTRACT(name, '$.ar') LIKE ?", ["%{$searchQuery}%"])
                    ->orWhereRaw("JSON_EXTRACT(name, '$.en') LIKE ?", ["%{$searchQuery}%"]);
            })
            ->with(['club']);

        if ($request->city_id) {
            $query->where('city_id', $request->city_id);
        }

        $venues = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => VenueResource::collection($venues),
            'meta' => [
                'current_page' => $venues->currentPage(),
                'last_page' => $venues->lastPage(),
                'per_page' => $venues->perPage(),
                'total' => $venues->total(),
            ],
        ]);
    }

    /**
     * Get available slots
     *
     * Checks slot availability for a venue on a given date and duration.
     *
     * @unauthenticated
     *
     * @queryParam date string required Date in Y-m-d format. Example: 2026-04-25
     * @queryParam duration_minutes integer required Duration (30–240, multiple of 30). Example: 60
     *
     * @response 200 {"success": true, "data": {"available": true, "unavailable_reason": null}}
     * @response 200 {"success": true, "data": {"available": false, "unavailable_reason": "Venue is inactive"}}
     */
    public function availableSlots(GetAvailableSlotsRequest $request, Venue $venue): JsonResponse
    {
        $result = $this->slotAvailabilityService->check(
            venueId: $venue->id,
            date: $request->date,
            startTime: '00:00',
            durationMinutes: $request->duration_minutes,
        );

        return response()->json([
            'success' => true,
            'data' => [
                'available' => $result->available,
                'unavailable_reason' => $result->unavailableReason,
            ],
        ]);
    }
}
