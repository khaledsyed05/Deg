<?php

namespace App\Http\Controllers\Club;

use App\Enums\BookingStatus;
use App\Enums\DepositStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Club;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response|RedirectResponse
    {
        $user = Auth::user();

        $club = Club::where('owner_id', $user->id)->first()
            ?? $user->clubs()->first();

        if (! $club) {
            return redirect()
                ->route('club.login')
                ->with('error', 'لا يوجد نادٍ مرتبط بحسابك.');
        }

        $today = today();
        $locale = app()->getLocale();

        $venueIds = $club->venues()->pluck('id');

        $activeStatuses = [BookingStatus::Confirmed, BookingStatus::Scheduled, BookingStatus::Completed];

        $todayBookingsCount = Booking::whereIn('venue_id', $venueIds)
            ->whereDate('booking_date', $today)
            ->whereIn('status', $activeStatuses)
            ->count();

        $todayTotalSlots = max($venueIds->count() * 12, 1); // assume 12 hourly slots per venue per day

        $occupancyRate = (int) round(($todayBookingsCount / $todayTotalSlots) * 100);

        $todayRevenue = (int) Booking::whereIn('venue_id', $venueIds)
            ->whereDate('booking_date', $today)
            ->whereIn('status', $activeStatuses)
            ->sum('total_price');

        $upcomingBookings = Booking::whereIn('venue_id', $venueIds)
            ->where('booking_date', '>', $today)
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Scheduled])
            ->count();

        $unpaidBookings = Booking::whereIn('venue_id', $venueIds)
            ->whereDate('booking_date', $today)
            ->whereIn('status', $activeStatuses)
            ->where('deposit_status', DepositStatus::None)
            ->count();

        $schedule = Booking::with(['venue', 'user:id,name'])
            ->whereIn('venue_id', $venueIds)
            ->whereDate('booking_date', $today)
            ->orderBy('start_time')
            ->get()
            ->map(fn (Booking $b) => [
                'id' => $b->id,
                'venue_name' => $b->venue?->getTranslation('name', $locale) ?: '—',
                'start_time' => substr((string) $b->start_time, 0, 5),
                'end_time' => substr((string) $b->end_time, 0, 5),
                'price' => (int) $b->total_price,
                'status' => $b->status?->value,
                'deposit_status' => $b->deposit_status?->value,
                'player_name' => $b->user?->name ?? '—',
            ]);

        return Inertia::render('Club/Dashboard/Index', [
            'stats' => [
                'today_bookings' => $todayBookingsCount,
                'total_slots' => $todayTotalSlots,
                'occupancy_rate' => $occupancyRate,
                'today_revenue' => $todayRevenue,
                'upcoming_bookings' => $upcomingBookings,
                'unpaid_bookings' => $unpaidBookings,
            ],
            'schedule' => $schedule,
            'today_iso' => $today->toIso8601String(),
            'today_day_key' => strtolower($today->format('l')),
        ]);
    }
}
