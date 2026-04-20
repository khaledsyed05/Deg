<?php

namespace App\Http\Controllers\Api\Club\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Club\V1\Venue\CreateVenueRequest;
use App\Http\Requests\Api\Club\V1\Venue\UpdateVenueRequest;
use App\Http\Resources\VenueResource;
use App\Models\Club;
use App\Models\Venue;
use App\Repositories\Contracts\VenueRepositoryInterface;
use Illuminate\Http\JsonResponse;

class VenueController extends Controller
{
    public function __construct(
        private VenueRepositoryInterface $venueRepo,
    ) {}

    /**
     * List club venues
     *
     * Returns all venues belonging to the club. Club managers only.
     *
     * @response 200 {"success": true, "data": [{"id": 1, "name": {"ar": "ملعب النور"}}]}
     * @response 403 {"message": "Forbidden"}
     */
    public function index(Club $club): JsonResponse
    {
        $this->authorize('manageVenues', $club);

        $venues = $this->venueRepo->findByClub($club->id);

        return response()->json([
            'success' => true,
            'data' => VenueResource::collection($venues),
        ]);
    }

    /**
     * Create a venue
     *
     * Creates a new venue for the club. Club managers only.
     *
     * @bodyParam name object required Venue name in Arabic and English. Example: {"ar": "ملعب النور", "en": "Al-Nour Field"}
     * @bodyParam price_per_hour integer required Price per hour in local currency. Example: 50000
     * @bodyParam capacity integer Venue capacity. Example: 10
     * @bodyParam opening_hours object Weekly opening schedule by day. Example: {"saturday": {"open": "08:00", "close": "22:00"}}
     *
     * @response 201 {"success": true, "data": {"id": 5, "name": {"ar": "ملعب النور"}}}
     * @response 403 {"message": "Forbidden"}
     */
    public function store(CreateVenueRequest $request, Club $club): JsonResponse
    {
        $this->authorize('manageVenues', $club);

        $data = $request->validated();
        $data['club_id'] = $club->id;

        if (isset($data['parsed_opening_hours'])) {
            $data['opening_hours'] = $data['parsed_opening_hours'];
            unset($data['parsed_opening_hours']);
        }

        $venue = $this->venueRepo->create($data);

        return response()->json([
            'success' => true,
            'data' => new VenueResource($venue),
        ], 201);
    }

    /**
     * Update a venue
     *
     * Updates venue details. Club managers only.
     *
     * @response 200 {"success": true, "data": {"id": 5, "name": {"ar": "ملعب النور المحدث"}}}
     * @response 403 {"message": "Forbidden"}
     */
    public function update(UpdateVenueRequest $request, Club $club, Venue $venue): JsonResponse
    {
        $this->authorize('update', $venue);

        $data = $request->validated();

        if (isset($data['parsed_opening_hours'])) {
            $data['opening_hours'] = $data['parsed_opening_hours'];
            unset($data['parsed_opening_hours']);
        }

        $venue = $this->venueRepo->update($venue, $data);

        return response()->json([
            'success' => true,
            'data' => new VenueResource($venue),
        ]);
    }

    /**
     * Delete a venue
     *
     * Permanently deletes a venue. Club managers only.
     *
     * @response 200 {"success": true}
     * @response 403 {"message": "Forbidden"}
     */
    public function destroy(Club $club, Venue $venue): JsonResponse
    {
        $this->authorize('delete', $venue);

        $this->venueRepo->delete($venue);

        return response()->json(['success' => true]);
    }
}
