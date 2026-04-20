<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Booking\CancelBookingRequest;
use App\Http\Requests\Api\V1\Booking\CreateBookingRequest;
use App\Http\Requests\Api\V1\Booking\ListBookingsRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\Booking\BookingService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class BookingController extends Controller
{
    public function __construct(
        private BookingService $bookingService,
        private BookingRepositoryInterface $bookingRepo,
    ) {}

    /**
     * List my bookings
     *
     * Returns a paginated list of the authenticated user's bookings.
     * Supports filtering by status and date range.
     *
     * @queryParam status string Filter by booking status (pending_payment, confirmed, completed, cancelled). Example: confirmed
     * @queryParam from_date string Filter bookings from this date (Y-m-d). Example: 2026-04-01
     * @queryParam to_date string Filter bookings up to this date (Y-m-d). Example: 2026-04-30
     * @queryParam per_page integer Results per page (default: 15). Example: 20
     *
     * @response 200 {"success": true, "data": [...], "meta": {"current_page": 1, "total": 8}}
     */
    public function index(ListBookingsRequest $request): JsonResponse
    {
        $query = $this->bookingRepo->query()
            ->where('user_id', $request->user()->id)
            ->with(['venue.club']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->from_date) {
            $query->where('booking_date', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->where('booking_date', '<=', $request->to_date);
        }

        $bookings = $query->orderByDesc('booking_date')->paginate($request->per_page ?? 15);

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

    /**
     * Create a booking
     *
     * Creates a new venue booking. Returns booking details with commission breakdown.
     * Booking status starts as `pending_payment` until payment is confirmed.
     *
     * @bodyParam venue_id integer required Venue ID. Example: 1
     * @bodyParam booking_date string required Date in Y-m-d format. Example: 2026-04-25
     * @bodyParam start_time string required Start time in H:i format. Example: 18:00
     * @bodyParam duration_minutes integer required Duration (30–240 minutes, multiples of 30). Example: 60
     * @bodyParam payment_mode string required Payment mode (full, deposit). Example: deposit
     * @bodyParam payment_provider string required Provider (mtn_cash, syriatel_cash, fatora, wallet). Example: mtn_cash
     * @bodyParam notes string Optional booking notes (max 500 chars). Example: أرجو تجهيز الملعب
     *
     * @response 201 {"success": true, "data": {"id": 123, "booking_code": "BK12345", "status": "pending_payment", "total_price": 50000}, "commission": {"total_price": 50000, "commission_amount": 3500, "club_payout": 46500}}
     * @response 422 {"success": false, "errors": {"venue_id": ["الملعب مطلوب"]}}
     */
    public function store(CreateBookingRequest $request): JsonResponse
    {
        $result = $this->bookingService->create(
            userId: $request->user()->id,
            venueId: $request->venue_id,
            data: $request->validated(),
        );

        return response()->json([
            'success' => true,
            'data' => new BookingResource($result->booking),
            'commission' => [
                'total_price' => $result->commission->totalPrice,
                'commission_amount' => $result->commission->commissionAmount,
                'club_payout' => $result->commission->clubPayoutAmount,
            ],
        ], 201);
    }

    /**
     * Get booking details
     *
     * Returns full details for a single booking. Users can only view their own bookings.
     *
     * @response 200 {"success": true, "data": {"id": 123, "booking_code": "BK12345", "status": "confirmed"}}
     * @response 403 {"message": "Forbidden"}
     * @response 404 {"message": "Not Found"}
     */
    public function show(Booking $booking): JsonResponse
    {
        $this->authorize('view', $booking);

        return response()->json([
            'success' => true,
            'data' => new BookingResource($booking->load(['venue.club'])),
        ]);
    }

    /**
     * Cancel a booking
     *
     * Cancels a booking and calculates the applicable refund.
     * Requires `confirmed: true` in the request body as a user intent confirmation.
     *
     * @bodyParam confirmed boolean required Must be true to confirm cancellation. Example: true
     *
     * @response 200 {"success": true, "data": {"id": 123, "status": "cancelled"}, "refund": 12000}
     * @response 403 {"message": "Forbidden"}
     * @response 422 {"success": false, "message": "Booking cannot be cancelled in its current status"}
     */
    public function cancel(CancelBookingRequest $request, Booking $booking): JsonResponse
    {
        $this->authorize('cancel', $booking);

        try {
            $result = $this->bookingService->cancel(
                booking: $booking,
                cancelledBy: $request->user()->id,
                reason: 'user_request',
            );
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => new BookingResource($result->booking),
            'refund' => $result->refundAmount,
        ]);
    }
}
