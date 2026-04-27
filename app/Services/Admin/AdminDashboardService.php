<?php

namespace App\Services\Admin;

use App\Models\Booking;
use App\Models\Club;
use App\Models\RefundRequest;
use App\Models\ReviewReport;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AdminDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        return Cache::remember('admin_dashboard_stats', 300, fn () => [
            'total_users' => User::count(),
            'total_bookings' => Booking::count(),
            'total_revenue' => (int) Booking::whereIn('status', ['confirmed', 'completed'])->sum('total_price'),
            'today_bookings' => Booking::whereDate('created_at', today())->count(),
            'today_revenue' => (int) Booking::whereDate('created_at', today())
                ->whereIn('status', ['confirmed', 'completed'])->sum('total_price'),
            'total_venues' => Venue::count(),
            'total_clubs' => Club::count(),
            'pending_clubs' => Club::where('status', 'pending_approval')->count(),
            'pending_refunds' => RefundRequest::where('status', 'pending_review')->count(),
            'open_tickets' => SupportTicket::whereIn('status', ['open', 'awaiting_agent_reply'])->count(),
            'pending_venue_reports' => VenueReport::where('status', 'pending')->count(),
            'pending_review_reports' => ReviewReport::where('status', 'pending')->count(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRevenue(string $period = 'monthly', ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from ?? now()->subDays(30);
        $to = $to ?? now();

        $expr = match ($period) {
            'daily' => 'DATE(created_at)',
            'weekly' => 'YEARWEEK(created_at)',
            'yearly' => 'YEAR(created_at)',
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };

        $rows = Booking::whereIn('status', ['confirmed', 'completed'])
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("$expr as period, SUM(total_price) as revenue, COUNT(*) as bookings")
            ->groupByRaw($expr)
            ->orderBy('period')
            ->get();

        return [
            'period_type' => $period,
            'from' => $from->toIso8601String(),
            'to' => $to->toIso8601String(),
            'data' => $rows->toArray(),
            'total_revenue' => (int) $rows->sum('revenue'),
            'total_bookings' => (int) $rows->sum('bookings'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getBookingsTrend(int $days = 30): array
    {
        return Booking::where('created_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as bookings')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    public function getTopVenues(int $limit = 10): array
    {
        return Venue::withCount(['bookings' => fn ($q) => $q->where('created_at', '>=', now()->subDays(30))])
            ->orderByDesc('bookings_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getUserGrowth(int $days = 30): array
    {
        return User::where('created_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as new_users')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }
}
