<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Waitlist\JoinWaitlistRequest;
use App\Http\Resources\WaitlistResource;
use App\Models\VenueWaitlist;
use App\Repositories\Contracts\VenueWaitlistRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

class WaitlistController extends Controller
{
    public function __construct(
        private VenueWaitlistRepositoryInterface $waitlistRepo,
    ) {}

    /**
     * List my waitlist entries
     *
     * Returns all active waitlist entries for the authenticated user.
     *
     * @response 200 {"success": true, "data": [{"id": 1, "venue_id": 5, "booking_date": "2026-04-25"}]}
     */
    public function index(): JsonResponse
    {
        $entries = $this->waitlistRepo->findForUser(auth()->id());

        return response()->json([
            'success' => true,
            'data' => WaitlistResource::collection($entries),
        ]);
    }

    /**
     * Join waitlist
     *
     * Adds the authenticated user to the waitlist for a venue slot.
     * One entry per user per venue/date/time combination is allowed.
     *
     * @bodyParam venue_id integer required Venue ID to join the waitlist for. Example: 5
     * @bodyParam preferred_date string required Preferred date (Y-m-d). Example: 2026-04-25
     * @bodyParam preferred_time string Preferred start time (H:i). Example: 18:00
     * @bodyParam duration_minutes integer Preferred duration in minutes. Example: 60
     *
     * @response 201 {"success": true, "data": {"id": 1, "venue_id": 5, "booking_date": "2026-04-25"}}
     * @response 422 {"success": false, "message": "You are already on the waitlist for this slot."}
     */
    public function store(JoinWaitlistRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $bookingDate = $validated['preferred_date'];

        try {
            $entry = $this->waitlistRepo->create([
                'venue_id' => $validated['venue_id'],
                'user_id' => $request->user()->id,
                'booking_date' => $bookingDate,
                'start_time' => $validated['preferred_time'] ?? '00:00',
                'duration_minutes' => $validated['duration_minutes'] ?? 60,
                'expires_at' => Carbon::parse($bookingDate)->addDay()->endOfDay(),
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are already on the waitlist for this slot.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => new WaitlistResource($entry),
        ], 201);
    }

    /**
     * Leave waitlist
     *
     * Removes the authenticated user from a waitlist entry they own.
     *
     * @response 200 {"success": true}
     * @response 403 {"message": "Forbidden"}
     * @response 404 {"message": "Not Found"}
     */
    public function destroy(VenueWaitlist $waitlist): JsonResponse
    {
        $this->authorize('delete', $waitlist);

        $this->waitlistRepo->delete($waitlist);

        return response()->json(['success' => true]);
    }
}
