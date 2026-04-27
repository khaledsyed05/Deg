<?php

namespace App\Http\Controllers\Api\V1\Club;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Booking;
use App\Models\Club;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinancialController extends Controller
{
    use ApiResponse;

    public function summary(Request $request): JsonResponse
    {
        $clubId = (int) $request->attributes->get('club_id');
        $venueIds = Club::findOrFail($clubId)->venues()->pluck('id');

        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now();

        $base = Booking::whereIn('venue_id', $venueIds)
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereBetween('booking_date', [$from, $to]);

        $perVenue = (clone $base)
            ->select('venue_id', DB::raw('SUM(total_price) as revenue'), DB::raw('COUNT(*) as bookings'))
            ->groupBy('venue_id')
            ->with('venue:id,name')
            ->get()
            ->map(fn ($row) => [
                'venue_id' => $row->venue_id,
                'venue_name' => $row->venue?->getTranslation('name', 'ar', false),
                'revenue' => (int) $row->revenue,
                'bookings' => (int) $row->bookings,
            ]);

        return $this->success([
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'totals' => [
                'revenue' => (int) (clone $base)->sum('total_price'),
                'bookings_count' => (clone $base)->count(),
                'avg_booking_value' => (int) (clone $base)->avg('total_price'),
            ],
            'per_venue' => $perVenue,
        ]);
    }
}
