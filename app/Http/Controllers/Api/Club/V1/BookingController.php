<?php

namespace App\Http\Controllers\Api\Club\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Club;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        private BookingRepositoryInterface $bookingRepo,
    ) {}

    /**
     * List club bookings
     *
     * Returns paginated bookings for all venues belonging to the club. Club managers only.
     *
     * @queryParam date string Filter bookings by date (Y-m-d). Example: 2026-04-25
     * @queryParam status string Filter by status (confirmed, completed, cancelled). Example: confirmed
     * @queryParam per_page integer Results per page (default: 20). Example: 20
     *
     * @response 200 {"success": true, "data": [...], "meta": {"total": 12}}
     * @response 403 {"message": "Forbidden"}
     */
    public function index(Request $request, Club $club): JsonResponse
    {
        $this->authorize('manageVenues', $club);

        $query = $this->bookingRepo->query()
            ->whereHas('venue', fn ($q) => $q->where('club_id', $club->id))
            ->with(['venue', 'user'])
            ->orderByDesc('booking_date');

        if ($request->get('date')) {
            $query->where('booking_date', $request->get('date'));
        }

        if ($request->get('status')) {
            $query->where('status', $request->get('status'));
        }

        $bookings = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => BookingResource::collection($bookings),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }
}
