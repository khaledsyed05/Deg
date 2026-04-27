<?php

namespace App\Http\Controllers\Api\V1\Club;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Booking;
use App\Models\Club;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManageBookingController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $clubId = (int) $request->attributes->get('club_id');
        $venueIds = Club::findOrFail($clubId)->venues()->pluck('id');

        $query = Booking::whereIn('venue_id', $venueIds)
            ->with(['user:id,name,phone_number', 'venue:id,name']);

        if ($venueId = $request->integer('venue_id')) {
            $query->where('venue_id', $venueId);
        }

        if ($status = $request->string('status')->value()) {
            $query->where('status', $status);
        }

        if ($from = $request->date('from')) {
            $query->where('booking_date', '>=', $from);
        }

        if ($to = $request->date('to')) {
            $query->where('booking_date', '<=', $to);
        }

        $bookings = $query->latest('booking_date')->paginate(20);

        return $this->success([
            'data' => $bookings->items(),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $clubId = (int) $request->attributes->get('club_id');
        $venueIds = Club::findOrFail($clubId)->venues()->pluck('id');

        $booking = Booking::whereIn('venue_id', $venueIds)
            ->with(['user', 'venue', 'payments'])
            ->findOrFail($id);

        return $this->success($booking);
    }

    public function checkIn(int $id, Request $request): JsonResponse
    {
        $booking = $this->ownedBooking($id, $request);
        $booking->update([
            'checked_in_at' => now(),
            'check_in_status' => 'checked_in',
        ]);

        return $this->success(['booking_id' => $booking->id, 'status' => 'checked_in'], 'تم تسجيل الحضور');
    }

    public function noShow(int $id, Request $request): JsonResponse
    {
        $booking = $this->ownedBooking($id, $request);
        $booking->update(['check_in_status' => 'no_show']);

        return $this->success(['booking_id' => $booking->id, 'status' => 'no_show'], 'تم تسجيل عدم الحضور');
    }

    public function complete(int $id, Request $request): JsonResponse
    {
        $booking = $this->ownedBooking($id, $request);
        $booking->update(['status' => 'completed']);

        return $this->success(['booking_id' => $booking->id, 'status' => 'completed'], 'تم اكتمال الحجز');
    }

    private function ownedBooking(int $id, Request $request): Booking
    {
        $clubId = (int) $request->attributes->get('club_id');
        $venueIds = Club::findOrFail($clubId)->venues()->pluck('id');

        return Booking::whereIn('venue_id', $venueIds)->findOrFail($id);
    }
}
