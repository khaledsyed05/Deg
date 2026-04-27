<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Exceptions\Booking\RefundException;
use App\Exceptions\Booking\RescheduleException;
use App\Exceptions\Booking\SplitPaymentException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Booking\CancelBookingRequest;
use App\Http\Requests\Api\V1\Booking\CheckAvailabilityRequest;
use App\Http\Requests\Api\V1\Booking\CreateBookingRequest;
use App\Http\Requests\Api\V1\Booking\ListBookingsRequest;
use App\Http\Requests\Api\V1\Booking\RequestRefundRequest;
use App\Http\Requests\Api\V1\Booking\RescheduleBookingRequest;
use App\Http\Requests\Api\V1\Booking\SplitPaymentRequest;
use App\Http\Resources\BookingResource;
use App\Http\Resources\V1\Booking\BookingDetailResource;
use App\Http\Resources\V1\Booking\BookingListResource;
use App\Http\Traits\ApiResponse;
use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\Booking\AvailabilityService;
use App\Services\Booking\BookingService;
use App\Services\Booking\PricingService;
use App\Services\Booking\RefundService;
use App\Services\Booking\RescheduleService;
use App\Services\Booking\SplitPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use RuntimeException;

class BookingController extends Controller
{
    use ApiResponse;

    public function __construct(
        private BookingService $bookingService,
        private BookingRepositoryInterface $bookingRepo,
        private AvailabilityService $availability,
        private PricingService $pricingService,
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

    /**
     * Check if a venue slot is available for booking.
     */
    public function checkAvailability(CheckAvailabilityRequest $request): JsonResponse
    {
        $result = $this->availability->check(
            venueId: $request->integer('venue_id'),
            date: $request->input('booking_date'),
            startTime: $request->input('start_time'),
            durationMinutes: $request->integer('duration_minutes'),
        );

        $pricing = $this->pricingService->calculate(
            venueId: $request->integer('venue_id'),
            durationMinutes: $request->integer('duration_minutes'),
            promoCode: $request->input('promo_code'),
        );

        return $this->success([
            'available' => $result->available,
            'unavailable_reason' => $result->unavailableReason,
            'venue_id' => $request->integer('venue_id'),
            'booking_date' => $request->input('booking_date'),
            'start_time' => $request->input('start_time'),
            'duration_minutes' => $request->integer('duration_minutes'),
            'pricing' => $pricing,
        ], null, $result->available ? 200 : 409);
    }

    /**
     * Calculate price breakdown for a potential booking.
     */
    public function calculatePrice(Request $request): JsonResponse
    {
        $data = $request->validate([
            'venue_id' => ['required', 'integer', 'exists:venues,id'],
            'duration_minutes' => ['required', 'integer', 'min:30', 'max:240', 'multiple_of:30'],
            'promo_code' => ['nullable', 'string', 'max:50'],
        ]);

        return $this->success($this->pricingService->calculate(
            venueId: (int) $data['venue_id'],
            durationMinutes: (int) $data['duration_minutes'],
            promoCode: $data['promo_code'] ?? null,
        ));
    }

    /**
     * Upcoming bookings for the authenticated user.
     */
    public function upcoming(Request $request): AnonymousResourceCollection
    {
        $bookings = $this->bookingRepo->query()
            ->forUser($request->user()->id)
            ->with(['venue.club.city', 'venue.media'])
            ->upcoming()
            ->paginate((int) ($request->integer('per_page') ?: 15));

        return BookingListResource::collection($bookings);
    }

    /**
     * Past bookings for the authenticated user.
     */
    public function past(Request $request): AnonymousResourceCollection
    {
        $bookings = $this->bookingRepo->query()
            ->forUser($request->user()->id)
            ->with(['venue.club.city', 'venue.media'])
            ->past()
            ->paginate((int) ($request->integer('per_page') ?: 15));

        return BookingListResource::collection($bookings);
    }

    /**
     * Receipt payload for a booking (lightweight JSON, for client-side rendering).
     */
    public function receipt(Booking $booking, Request $request): JsonResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        $booking->load(['venue.club.city', 'payments']);

        return $this->success([
            'booking_code' => $booking->booking_code,
            'venue_name' => $booking->venue?->name,
            'club_name' => $booking->venue?->club?->name,
            'city' => $booking->venue?->club?->city?->name,
            'booking_date' => $booking->booking_date?->format('Y-m-d'),
            'time' => "{$booking->start_time} – {$booking->end_time}",
            'duration_minutes' => (int) $booking->duration_minutes,
            'total_price' => (int) $booking->total_price,
            'deposit_amount' => (int) $booking->deposit_amount,
            'remaining_amount' => (int) $booking->remaining_amount,
            'discount_amount' => (int) $booking->discount_amount,
            'currency' => $booking->currency ?? 'SYP',
            'status' => $booking->status?->value,
            'deposit_status' => $booking->deposit_status?->value,
            'remaining_status' => $booking->remaining_status?->value,
            'payments' => $booking->payments->map(fn ($p) => [
                'id' => $p->id,
                'provider' => $p->provider?->value,
                'status' => $p->status?->value,
                'amount' => (int) $p->amount,
                'completed_at' => $p->completed_at?->toISOString(),
            ])->values(),
            'issued_at' => now()->toISOString(),
        ]);
    }

    /**
     * Mark the booking as checked in (QR scan at the venue).
     */
    public function checkin(Booking $booking, Request $request): JsonResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        if (! in_array($booking->status, [BookingStatus::Confirmed, BookingStatus::Scheduled], true)) {
            return $this->error(__('bookings.cannot_check_in'), null, 400);
        }

        if (! $booking->isToday()) {
            return $this->error(__('bookings.checkin_today_only'), null, 400);
        }

        $booking->checkIn();

        return $this->success(
            new BookingDetailResource($booking->fresh()->load(['venue.club.city'])),
            __('bookings.checked_in'),
        );
    }

    public function reschedule(int $id, RescheduleBookingRequest $request, RescheduleService $service): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        try {
            $rescheduled = $service->reschedule(
                $booking,
                $request->user(),
                $request->validated('new_slot_date'),
                $request->validated('new_start_time'),
                $request->validated('new_end_time'),
                $request->validated('reason'),
            );
        } catch (RescheduleException $e) {
            return $this->error($e->getMessage(), null, $e->statusCode);
        }

        $history = $rescheduled->reschedule_history ?? [];
        $latest = end($history) ?: [];

        return $this->success([
            'booking_id' => $rescheduled->id,
            'previous_schedule' => $latest['from'] ?? null,
            'new_schedule' => $latest['to'] ?? null,
            'price_difference' => $latest['price_difference'] ?? 0,
            'reschedule_count' => $rescheduled->reschedule_count,
            'remaining_reschedules_allowed' => max(0,
                (int) config('bookings.reschedule.max_per_booking', 2) - (int) $rescheduled->reschedule_count
            ),
        ], 'تم تغيير موعد الحجز بنجاح');
    }

    public function requestRefund(int $id, RequestRefundRequest $request, RefundService $service): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        try {
            $refundRequest = $service->requestRefund(
                $booking,
                $request->user(),
                $request->validated('reason'),
                $request->validated('refund_method', 'wallet') ?? 'wallet',
            );
        } catch (RefundException $e) {
            return $this->error($e->getMessage(), null, $e->statusCode);
        }

        return $this->success([
            'refund_request_id' => $refundRequest->id,
            'status' => $refundRequest->status,
            'approved_amount' => $refundRequest->approved_amount,
            'requested_amount' => $refundRequest->requested_amount,
            'refund_method' => $refundRequest->refund_method,
            'policy_applied' => $refundRequest->policy_applied,
            'auto_approved' => (bool) $refundRequest->auto_approved,
            'estimated_processing_time_minutes' => $refundRequest->refund_method === 'wallet' ? 5 : null,
            'estimated_review_time_hours' => $refundRequest->status === 'pending_review' ? 24 : null,
        ], $refundRequest->auto_approved
            ? 'تم إصدار طلب الاسترداد بنجاح'
            : 'تم استلام طلب الاسترداد، سيتم مراجعته قريباً');
    }

    public function splitPayment(int $id, SplitPaymentRequest $request, SplitPaymentService $service): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        try {
            $result = $service->createSplit(
                $booking,
                $request->user(),
                $request->validated('split_method'),
                (array) $request->validated('splits'),
            );
        } catch (SplitPaymentException $e) {
            return $this->error($e->getMessage(), null, $e->statusCode);
        }

        return $this->success($result, 'تم تقسيم الدفع بنجاح');
    }
}
