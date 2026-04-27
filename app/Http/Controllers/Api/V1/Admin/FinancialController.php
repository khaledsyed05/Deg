<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialController extends Controller
{
    use ApiResponse;

    public function revenue(Request $request): JsonResponse
    {
        $from = $request->date('from') ?? now()->subDays(30);
        $to = $request->date('to') ?? now();

        $query = Booking::whereIn('status', ['confirmed', 'completed'])
            ->whereBetween('created_at', [$from, $to]);

        if ($venueId = $request->integer('venue_id')) {
            $query->where('venue_id', $venueId);
        }

        if ($clubId = $request->integer('club_id')) {
            $query->whereHas('venue', fn ($q) => $q->where('club_id', $clubId));
        }

        $perPage = min(100, max(10, $request->integer('per_page', 50)));
        $bookings = $query->with(['venue:id,name,club_id', 'venue.club:id,name'])
            ->latest()
            ->paginate($perPage);

        $total = (clone $query)->sum('total_price');

        return $this->success([
            'data' => $bookings->items(),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'total' => $bookings->total(),
            ],
            'totals' => [
                'revenue' => (int) $total,
                'bookings_count' => $bookings->total(),
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ]);
    }

    public function payments(Request $request): JsonResponse
    {
        $query = Payment::query()->with(['user:id,name', 'booking:id,booking_code']);

        if ($status = $request->string('status')->value()) {
            $query->where('status', $status);
        }

        if ($provider = $request->string('provider')->value()) {
            $query->where('provider', $provider);
        }

        $payments = $query->latest()->paginate(50);

        return $this->success([
            'data' => $payments->items(),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    public function walletFlow(Request $request): JsonResponse
    {
        $from = $request->date('from') ?? now()->subDays(30);
        $to = $request->date('to') ?? now();

        $rows = WalletTransaction::whereBetween('created_at', [$from, $to])
            ->selectRaw('credit_type, type, COUNT(*) as count, SUM(amount) as total_amount')
            ->groupBy('credit_type', 'type')
            ->get();

        return $this->success([
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'breakdown' => $rows->toArray(),
        ]);
    }
}
