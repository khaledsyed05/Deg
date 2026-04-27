<?php

namespace App\Http\Controllers\Api\V1\Club;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Booking;
use App\Models\Venue;
use App\Models\VenueBlockedSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    use ApiResponse;

    public function index(int $id, Request $request): JsonResponse
    {
        $clubId = (int) $request->attributes->get('club_id');
        $venue = Venue::where('id', $id)->where('club_id', $clubId)->firstOrFail();

        $from = $request->date('from') ?? now()->startOfWeek();
        $to = $request->date('to') ?? now()->endOfWeek();

        $bookings = Booking::where('venue_id', $venue->id)
            ->whereBetween('booking_date', [$from, $to])
            ->whereNotIn('status', ['cancelled', 'pending_payment'])
            ->select('id', 'booking_date', 'start_time', 'end_time', 'status', 'user_id')
            ->with('user:id,name')
            ->get();

        $blocks = VenueBlockedSlot::where('venue_id', $venue->id)
            ->whereBetween('blocked_date', [$from, $to])
            ->get();

        return $this->success([
            'venue_id' => $venue->id,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'bookings' => $bookings,
            'blocked_slots' => $blocks,
        ]);
    }

    public function blockSlot(int $id, Request $request): JsonResponse
    {
        $clubId = (int) $request->attributes->get('club_id');
        $venue = Venue::where('id', $id)->where('club_id', $clubId)->firstOrFail();

        $data = $request->validate([
            'blocked_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'reason' => 'sometimes|in:maintenance,private_event,staff_unavailable,other',
            'notes' => 'sometimes|nullable|string|max:500',
        ]);

        $block = VenueBlockedSlot::create([
            'venue_id' => $venue->id,
            'created_by' => $request->user()->id,
            'blocked_date' => $data['blocked_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'reason' => $data['reason'] ?? 'other',
            'notes' => $data['notes'] ?? null,
        ]);

        return $this->success($block, 'تم حجز الفترة', 201);
    }

    public function unblockSlot(int $id, int $blockId, Request $request): JsonResponse
    {
        $clubId = (int) $request->attributes->get('club_id');
        $venue = Venue::where('id', $id)->where('club_id', $clubId)->firstOrFail();

        $block = VenueBlockedSlot::where('id', $blockId)->where('venue_id', $venue->id)->firstOrFail();
        $block->delete();

        return $this->success([], 'تم إلغاء الحجز');
    }
}
