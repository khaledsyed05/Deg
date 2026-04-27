<?php

namespace App\Http\Controllers\Club;

use App\Enums\BookingStatus;
use App\Enums\CommissionScope;
use App\Enums\CommissionType;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Club;
use App\Models\CommissionConfig;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class FinanceController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $dateFrom = $this->parseDate($request->string('date_from')->toString(), now()->subDays(30));
        $dateTo = $this->parseDate($request->string('date_to')->toString(), now());
        if ($dateTo->lt($dateFrom)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $locale = app()->getLocale();
        $venueIds = $club->venues()->pluck('id');

        $completed = BookingStatus::Completed->value;

        $base = Booking::query()
            ->whereIn('venue_id', $venueIds)
            ->where('status', $completed)
            ->whereBetween('booking_date', [$dateFrom->toDateString(), $dateTo->toDateString()]);

        $totals = (clone $base)
            ->selectRaw('COALESCE(SUM(total_price), 0) as revenue')
            ->selectRaw('COALESCE(SUM(commission_amount), 0) as commission')
            ->selectRaw('COALESCE(SUM(club_payout_amount), 0) as net')
            ->first();

        $totalRevenue = (int) ($totals->revenue ?? 0);
        $totalCommission = (int) ($totals->commission ?? 0);
        $netPayout = (int) ($totals->net ?? 0);

        $pendingSettlements = (int) Booking::query()
            ->whereIn('venue_id', $venueIds)
            ->where('status', $completed)
            ->whereNotIn('id', DB::table('settlement_items')->select('booking_id'))
            ->sum('club_payout_amount');

        $effectiveRate = $totalRevenue > 0
            ? round(($totalCommission / $totalRevenue) * 100, 2)
            : $this->resolveCommissionRatePercent($club->id);

        // Revenue trend — daily
        $revenueTrend = (clone $base)
            ->selectRaw('DATE(booking_date) as date, SUM(total_price) as revenue, SUM(club_payout_amount) as net')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => (string) $row->date,
                'revenue' => (int) $row->revenue,
                'net' => (int) $row->net,
            ]);

        // Revenue by venue
        $revenueByVenue = (clone $base)
            ->with(['venue:id,name'])
            ->selectRaw('venue_id, SUM(total_price) as revenue, SUM(club_payout_amount) as net, COUNT(*) as bookings')
            ->groupBy('venue_id')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row) => [
                'venue' => $row->venue?->getTranslation('name', $locale) ?: $row->venue?->getTranslation('name', 'ar') ?: '—',
                'revenue' => (int) $row->revenue,
                'net' => (int) $row->net,
                'bookings' => (int) $row->bookings,
            ]);

        // Recent transactions
        $settledBookingIds = DB::table('settlement_items')
            ->join('settlements', 'settlements.id', '=', 'settlement_items.settlement_id')
            ->whereIn('settlement_items.booking_id', (clone $base)->select('id'))
            ->pluck('settlement_items.booking_id')
            ->flip();

        $transactions = (clone $base)
            ->with(['venue:id,slug,name', 'user:id,name'])
            ->orderByDesc('booking_date')
            ->orderByDesc('start_time')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Booking $b) => [
                'id' => $b->id,
                'booking_code' => $b->booking_code,
                'date' => $b->booking_date?->toDateString(),
                'venue' => [
                    'slug' => $b->venue?->slug,
                    'name' => $b->venue?->getTranslation('name', $locale) ?: $b->venue?->getTranslation('name', 'ar'),
                ],
                'player' => $b->user?->name ?? '—',
                'total_amount' => (int) $b->total_price,
                'commission' => (int) $b->commission_amount,
                'net_amount' => (int) $b->club_payout_amount,
                'is_settled' => isset($settledBookingIds[$b->id]),
            ]);

        return Inertia::render('Club/Finances/Index', [
            'stats' => [
                'total_revenue' => $totalRevenue,
                'net_payout' => $netPayout,
                'commission' => $totalCommission,
                'pending_settlements' => $pendingSettlements,
            ],
            'commissionRate' => $effectiveRate,
            'revenueTrend' => $revenueTrend,
            'revenueByVenue' => $revenueByVenue,
            'transactions' => $transactions,
            'filters' => [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
            ],
        ]);
    }

    private function parseDate(string $input, Carbon $fallback): Carbon
    {
        if ($input === '') {
            return $fallback->copy()->startOfDay();
        }
        try {
            return Carbon::parse($input)->startOfDay();
        } catch (\Throwable) {
            return $fallback->copy()->startOfDay();
        }
    }

    /**
     * Best-effort: return the configured commission rate (as percent) for display,
     * falling back through club → global scopes. Fixed commissions return 0 (shown via effective rate instead).
     */
    private function resolveCommissionRatePercent(int $clubId): float
    {
        $config = CommissionConfig::query()
            ->where('is_active', true)
            ->where(function (Builder $q) use ($clubId) {
                $q->where(function (Builder $q) use ($clubId) {
                    $q->where('scope', CommissionScope::Club->value)->where('club_id', $clubId);
                })->orWhere('scope', CommissionScope::Global->value);
            })
            ->orderByRaw("FIELD(scope, 'club', 'global')")
            ->orderByDesc('effective_from')
            ->first();

        if (! $config) {
            return 0.0;
        }

        if (($config->commission_type instanceof CommissionType ? $config->commission_type->value : $config->commission_type) === CommissionType::Percentage->value) {
            // stored as basis points (e.g. 700 = 7.00%)
            return round(((int) $config->commission_value) / 100, 2);
        }

        return 0.0;
    }

    private function resolveClub(): Club|RedirectResponse
    {
        $user = Auth::user();

        $club = Club::where('owner_id', $user->id)->first()
            ?? $user->clubs()->first();

        if (! $club) {
            return redirect()
                ->route('club.dashboard')
                ->with('error', 'لا يوجد نادٍ مرتبط بحسابك.');
        }

        return $club;
    }
}
