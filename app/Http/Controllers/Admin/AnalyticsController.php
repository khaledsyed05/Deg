<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    /** Revenue analytics dashboard. */
    public function revenue(Request $request): Response
    {
        [$from, $to] = $this->resolveRange($request);
        $days = max(1, (int) $from->diffInDays($to) + 1);

        // Previous period of the same length, immediately before the current one.
        $prevTo = $from->copy()->subDay()->endOfDay();
        $prevFrom = $prevTo->copy()->subDays($days - 1)->startOfDay();

        $current = $this->overview($from, $to);
        $prev = $this->overview($prevFrom, $prevTo);

        $overview = [
            'total_revenue' => $current['revenue'],
            'total_revenue_prev' => $prev['revenue'],
            'revenue_change' => $this->pct($current['revenue'], $prev['revenue']),

            'total_commission' => $current['commission'],
            'total_commission_prev' => $prev['commission'],
            'commission_change' => $this->pct($current['commission'], $prev['commission']),

            'avg_booking_value' => $current['avg'],
            'avg_booking_value_prev' => $prev['avg'],
            'avg_booking_change' => $this->pct($current['avg'], $prev['avg']),

            'revenue_per_day' => $days > 0 ? (int) round($current['revenue'] / $days) : 0,
            'revenue_per_day_prev' => $days > 0 ? (int) round($prev['revenue'] / $days) : 0,
            'revenue_per_day_change' => $this->pct(
                $days > 0 ? $current['revenue'] / $days : 0,
                $days > 0 ? $prev['revenue'] / $days : 0,
            ),

            'total_bookings' => $current['count'],
            'total_bookings_prev' => $prev['count'],
        ];

        $granularity = $days <= 7 ? 'day' : ($days <= 90 ? 'week' : 'month');
        $trend = $this->trend($from, $to, $granularity);

        $revenueByVenue = $this->byVenue($from, $to, $current['revenue']);
        $revenueByClub = $this->byClub($from, $to, $current['revenue']);
        $revenueByCity = $this->byCity($from, $to, $current['revenue']);
        $revenueBySport = $this->bySport($from, $to, $current['revenue']);
        $paymentMethods = $this->paymentMethods($from, $to, $current['revenue']);
        $heatmap = $this->timeHeatmap($from, $to);

        return Inertia::render('Admin/Analytics/Revenue', [
            'filters' => [
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
                'granularity' => $granularity,
            ],
            'overview' => $overview,
            'trend' => $trend,
            'revenue_by_venue' => $revenueByVenue,
            'revenue_by_club' => $revenueByClub,
            'revenue_by_city' => $revenueByCity,
            'revenue_by_sport' => $revenueBySport,
            'payment_methods' => $paymentMethods,
            'heatmap' => $heatmap,
        ]);
    }

    /** Booking analytics dashboard. */
    public function bookings(Request $request): Response
    {
        [$from, $to] = $this->resolveRange($request);
        $days = max(1, (int) $from->diffInDays($to) + 1);

        $prevTo = $from->copy()->subDay()->endOfDay();
        $prevFrom = $prevTo->copy()->subDays($days - 1)->startOfDay();

        $current = $this->bookingsOverview($from, $to);
        $prev = $this->bookingsOverview($prevFrom, $prevTo);

        $currentRate = $current['total'] > 0 ? ($current['cancelled'] / $current['total']) * 100 : 0;
        $prevRate = $prev['total'] > 0 ? ($prev['cancelled'] / $prev['total']) * 100 : 0;

        $overview = [
            'total_bookings' => $current['total'],
            'total_bookings_prev' => $prev['total'],
            'total_bookings_change' => $this->pct($current['total'], $prev['total']),
            'confirmed_bookings' => $current['confirmed'],
            'confirmed_bookings_change' => $this->pct($current['confirmed'], $prev['confirmed']),
            'completed_bookings' => $current['completed'],
            'completed_bookings_change' => $this->pct($current['completed'], $prev['completed']),
            'cancelled_bookings' => $current['cancelled'],
            'cancelled_bookings_change' => $this->pct($current['cancelled'], $prev['cancelled']),
            'cancellation_rate' => round($currentRate, 2),
            'cancellation_rate_change' => $this->pct($currentRate, $prevRate),
            'avg_duration_hours' => $current['avg_duration_minutes'] > 0 ? round($current['avg_duration_minutes'] / 60, 2) : 0,
            'avg_duration_change' => $this->pct($current['avg_duration_minutes'], $prev['avg_duration_minutes']),
        ];

        $granularity = $days <= 7 ? 'day' : ($days <= 90 ? 'week' : 'month');
        $bookingTrends = $this->bookingsTrend($from, $to, $granularity);

        $peakHours = $this->bookingsPeakHours($from, $to);
        $popularVenues = $this->popularVenues($from, $to, $days);
        $sportDistribution = $this->bookingsBySport($from, $to, $current['total']);
        $durationBuckets = $this->durationBuckets($from, $to);
        $cancellationReasons = $this->cancellationReasons($from, $to, $current['cancelled']);
        $cancellationTiming = $this->cancellationTiming($from, $to);
        $advanceWindow = $this->advanceWindow($from, $to);

        return Inertia::render('Admin/Analytics/Bookings', [
            'filters' => [
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
                'granularity' => $granularity,
            ],
            'overview' => $overview,
            'booking_trends' => $bookingTrends,
            'peak_hours' => $peakHours,
            'popular_venues' => $popularVenues,
            'sport_distribution' => $sportDistribution,
            'duration_buckets' => $durationBuckets,
            'cancellation_reasons' => $cancellationReasons,
            'cancellation_timing' => $cancellationTiming,
            'advance_window' => $advanceWindow['buckets'],
            'avg_advance_days' => $advanceWindow['avg_days'],
        ]);
    }

    /** Player analytics dashboard. */
    public function players(Request $request): Response
    {
        [$from, $to] = $this->resolveRange($request);
        $days = max(1, (int) $from->diffInDays($to) + 1);

        $prevTo = $from->copy()->subDay()->endOfDay();
        $prevFrom = $prevTo->copy()->subDays($days - 1)->startOfDay();

        $totalPlayers = (int) $this->playerUserQuery()->count();
        $prevTotalPlayers = (int) $this->playerUserQuery()
            ->where('users.created_at', '<', $from->toDateTimeString())
            ->count();

        $activeIds = $this->activePlayerIds($from, $to);
        $prevActiveIds = $this->activePlayerIds($prevFrom, $prevTo);
        $activePlayers = $activeIds->count();
        $prevActivePlayers = $prevActiveIds->count();

        $newPlayers = (int) $this->playerUserQuery()
            ->whereBetween('users.created_at', [$from->toDateTimeString(), $to->toDateTimeString()])
            ->count();

        $prevNewPlayers = (int) $this->playerUserQuery()
            ->whereBetween('users.created_at', [$prevFrom->toDateTimeString(), $prevTo->toDateTimeString()])
            ->count();

        // Churned: had bookings before current period, none during it.
        $hadBefore = Booking::query()
            ->whereIn('status', ['confirmed', 'completed'])
            ->where('booking_date', '<', $from->toDateString())
            ->distinct()
            ->pluck('user_id');
        $churnedPlayers = $hadBefore->diff($activeIds)->count();

        // Retention = share of prev period's actives that are still active in current.
        $retained = $prevActiveIds->intersect($activeIds)->count();
        $retentionRate = $prevActivePlayers > 0 ? ($retained / $prevActivePlayers) * 100 : 0;

        $totalBookings = (int) Booking::query()
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereBetween('booking_date', [$from->toDateString(), $to->toDateString()])
            ->count();
        $avgBookingsPerPlayer = $activePlayers > 0 ? $totalBookings / $activePlayers : 0;

        $overview = [
            'total_players' => $totalPlayers,
            'total_players_change' => $this->pct($totalPlayers, $prevTotalPlayers),
            'active_players' => $activePlayers,
            'active_players_change' => $this->pct($activePlayers, $prevActivePlayers),
            'new_players' => $newPlayers,
            'new_players_change' => $this->pct($newPlayers, $prevNewPlayers),
            'churned_players' => $churnedPlayers,
            'retention_rate' => round($retentionRate, 1),
            'retention_rate_prev' => 0, // reserved; computing true vs-prev needs another window shift
            'avg_bookings_per_player' => round($avgBookingsPerPlayer, 2),
        ];

        $funnel = $this->buildFunnel($totalPlayers);
        $dropoff = $this->buildDropoff($funnel);
        $segmentation = $this->buildSegmentation($totalPlayers);
        $spendingTiers = $this->buildSpendingTiers($totalPlayers);
        $cohorts = $this->buildCohorts();
        $events = $this->fetchEvents($request);

        return Inertia::render('Admin/Analytics/Players', [
            'filters' => [
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
                'event_name' => $request->string('event_name')->toString(),
            ],
            'overview' => $overview,
            'funnel' => $funnel,
            'dropoff' => $dropoff,
            'segmentation' => $segmentation,
            'spending_tiers' => $spendingTiers,
            'cohorts' => $cohorts,
            'events' => $events,
            'event_name_options' => $this->distinctEventNames(),
        ]);
    }

    public function exportPlayers(Request $request): HttpResponse
    {
        [$from, $to] = $this->resolveRange($request);
        $days = max(1, (int) $from->diffInDays($to) + 1);
        $totalPlayers = (int) $this->playerUserQuery()->count();
        $funnel = $this->buildFunnel($totalPlayers);

        $filename = 'players-analytics-'.$from->toDateString().'_to_'.$to->toDateString().'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($from, $to, $days, $totalPlayers, $funnel) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($out, ['Player Analytics']);
            fputcsv($out, ['Range', $from->toDateString().' → '.$to->toDateString(), $days.' days']);
            fputcsv($out, ['Total players', $totalPlayers]);
            fputcsv($out, []);

            fputcsv($out, ['Funnel']);
            fputcsv($out, ['Step', 'Count', '% of prev step']);
            $prevCount = $funnel[0]['count'] ?? 0;
            foreach ($funnel as $step) {
                $rate = $prevCount > 0 ? round(($step['count'] / $prevCount) * 100, 2) : 0;
                fputcsv($out, [$step['step'], $step['count'], $rate.'%']);
                $prevCount = $step['count'];
            }
            fputcsv($out, []);

            fputcsv($out, ['Segmentation']);
            fputcsv($out, ['Segment', 'Players', '%', 'Bookings', 'Revenue']);
            foreach ($this->buildSegmentation($totalPlayers) as $seg) {
                fputcsv($out, [$seg['segment'], $seg['player_count'], $seg['percentage'].'%', $seg['total_bookings'], $seg['total_revenue']]);
            }
            fputcsv($out, []);

            fputcsv($out, ['Spending Tiers']);
            fputcsv($out, ['Tier', 'Players', '%', 'Revenue', 'Avg']);
            foreach ($this->buildSpendingTiers($totalPlayers) as $t) {
                fputcsv($out, [$t['tier'], $t['player_count'], $t['percentage'].'%', $t['total_revenue'], $t['avg_spending']]);
            }
            fputcsv($out, []);

            fputcsv($out, ['Retention cohorts']);
            fputcsv($out, ['Cohort', 'Size', 'M0', 'M1', 'M2', 'M3', 'M4', 'M5', 'M6']);
            foreach ($this->buildCohorts() as $c) {
                fputcsv($out, [
                    $c['month'], $c['size'],
                    $c['month_0'] ?? '', $c['month_1'] ?? '', $c['month_2'] ?? '',
                    $c['month_3'] ?? '', $c['month_4'] ?? '', $c['month_5'] ?? '', $c['month_6'] ?? '',
                ]);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportBookings(Request $request): HttpResponse
    {
        [$from, $to] = $this->resolveRange($request);
        $days = max(1, (int) $from->diffInDays($to) + 1);
        $current = $this->bookingsOverview($from, $to);

        $filename = 'bookings-analytics-'.$from->toDateString().'_to_'.$to->toDateString().'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($from, $to, $days, $current) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($out, ['Bookings Analytics']);
            fputcsv($out, ['Range', $from->toDateString().' → '.$to->toDateString(), $days.' days']);
            fputcsv($out, ['Total', $current['total']]);
            fputcsv($out, ['Confirmed', $current['confirmed']]);
            fputcsv($out, ['Completed', $current['completed']]);
            fputcsv($out, ['Cancelled', $current['cancelled']]);
            fputcsv($out, ['Avg Duration (min)', $current['avg_duration_minutes']]);
            fputcsv($out, []);

            fputcsv($out, ['Peak Hours']);
            fputcsv($out, ['Hour', 'Bookings']);
            foreach ($this->bookingsPeakHours($from, $to) as $row) {
                fputcsv($out, [$row['hour'], $row['bookings']]);
            }
            fputcsv($out, []);

            fputcsv($out, ['Popular Venues']);
            fputcsv($out, ['Rank', 'Venue', 'Club', 'City', 'Bookings', 'Avg Rating', 'Occupancy %']);
            foreach ($this->popularVenues($from, $to, $days) as $i => $row) {
                fputcsv($out, [
                    $i + 1,
                    is_array($row['name']) ? ($row['name']['ar'] ?? $row['name']['en'] ?? '') : $row['name'],
                    is_array($row['club_name']) ? ($row['club_name']['ar'] ?? $row['club_name']['en'] ?? '') : ($row['club_name'] ?? ''),
                    $row['city_name_ar'] ?? $row['city_name'] ?? '',
                    $row['bookings_count'],
                    $row['avg_rating'] ?? '-',
                    $row['occupancy_rate'],
                ]);
            }
            fputcsv($out, []);

            fputcsv($out, ['By Sport']);
            fputcsv($out, ['Sport', 'Bookings', '%', 'Avg Duration (h)', 'Avg Price']);
            foreach ($this->bookingsBySport($from, $to, $current['total']) as $row) {
                fputcsv($out, [
                    is_array($row['name']) ? ($row['name']['ar'] ?? $row['name']['en'] ?? '') : $row['name'],
                    $row['bookings_count'],
                    round($row['percentage'], 2).'%',
                    $row['avg_duration_hours'],
                    $row['avg_price'],
                ]);
            }
            fputcsv($out, []);

            fputcsv($out, ['Duration Buckets']);
            fputcsv($out, ['Bucket', 'Count']);
            foreach ($this->durationBuckets($from, $to) as $row) {
                fputcsv($out, [$row['key'], $row['count']]);
            }
            fputcsv($out, []);

            fputcsv($out, ['Cancellation Reasons']);
            fputcsv($out, ['Reason', 'Count', '%']);
            foreach ($this->cancellationReasons($from, $to, $current['cancelled']) as $row) {
                fputcsv($out, [$row['reason'], $row['count'], round($row['percentage'], 2).'%']);
            }
            fputcsv($out, []);

            fputcsv($out, ['Advance Window']);
            fputcsv($out, ['Bucket', 'Count']);
            $aw = $this->advanceWindow($from, $to);
            foreach ($aw['buckets'] as $row) {
                fputcsv($out, [$row['key'], $row['count']]);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportRevenue(Request $request): HttpResponse
    {
        [$from, $to] = $this->resolveRange($request);
        $days = max(1, (int) $from->diffInDays($to) + 1);
        $current = $this->overview($from, $to);

        $filename = 'revenue-analytics-'.$from->toDateString().'_to_'.$to->toDateString().'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($from, $to, $days, $current) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($out, ['Revenue Analytics']);
            fputcsv($out, ['Range', $from->toDateString().' → '.$to->toDateString(), $days.' days']);
            fputcsv($out, ['Total Revenue', $current['revenue']]);
            fputcsv($out, ['Total Commission', $current['commission']]);
            fputcsv($out, ['Bookings', $current['count']]);
            fputcsv($out, ['Avg Booking Value', $current['avg']]);
            fputcsv($out, []);

            fputcsv($out, ['By Venue']);
            fputcsv($out, ['Rank', 'Venue', 'Club', 'Bookings', 'Revenue', 'Avg', '%']);
            foreach ($this->byVenue($from, $to, $current['revenue']) as $i => $row) {
                fputcsv($out, [
                    $i + 1,
                    is_array($row['name']) ? ($row['name']['ar'] ?? $row['name']['en'] ?? '') : $row['name'],
                    is_array($row['club_name'] ?? null) ? ($row['club_name']['ar'] ?? $row['club_name']['en'] ?? '') : ($row['club_name'] ?? ''),
                    $row['bookings_count'],
                    $row['total_revenue'],
                    $row['avg_booking_value'],
                    round($row['percentage'], 2).'%',
                ]);
            }
            fputcsv($out, []);

            fputcsv($out, ['By Club']);
            fputcsv($out, ['Rank', 'Club', 'Venues', 'Bookings', 'Revenue', 'Commission', 'Net', '%']);
            foreach ($this->byClub($from, $to, $current['revenue']) as $i => $row) {
                fputcsv($out, [
                    $i + 1,
                    is_array($row['name']) ? ($row['name']['ar'] ?? $row['name']['en'] ?? '') : $row['name'],
                    $row['venues_count'],
                    $row['bookings_count'],
                    $row['total_revenue'],
                    $row['commission'],
                    $row['net_amount'],
                    round($row['percentage'], 2).'%',
                ]);
            }
            fputcsv($out, []);

            fputcsv($out, ['By City']);
            fputcsv($out, ['City', 'Venues', 'Bookings', 'Revenue', '%']);
            foreach ($this->byCity($from, $to, $current['revenue']) as $row) {
                fputcsv($out, [
                    $row['name_ar'] ?? $row['name'],
                    $row['venues_count'],
                    $row['bookings_count'],
                    $row['total_revenue'],
                    round($row['percentage'], 2).'%',
                ]);
            }
            fputcsv($out, []);

            fputcsv($out, ['By Sport']);
            fputcsv($out, ['Sport', 'Bookings', 'Revenue', 'Avg', '%']);
            foreach ($this->bySport($from, $to, $current['revenue']) as $row) {
                fputcsv($out, [
                    is_array($row['name']) ? ($row['name']['ar'] ?? $row['name']['en'] ?? '') : $row['name'],
                    $row['bookings_count'],
                    $row['total_revenue'],
                    $row['avg_booking_value'],
                    round($row['percentage'], 2).'%',
                ]);
            }
            fputcsv($out, []);

            fputcsv($out, ['Payment Methods']);
            fputcsv($out, ['Provider', 'Bookings', 'Amount', '%']);
            foreach ($this->paymentMethods($from, $to, $current['revenue']) as $row) {
                fputcsv($out, [
                    $row['provider'],
                    $row['bookings_count'],
                    $row['total_amount'],
                    round($row['percentage'], 2).'%',
                ]);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ---------- helpers ----------

    /** @return array{0: Carbon, 1: Carbon} */
    private function resolveRange(Request $r): array
    {
        $fromIn = $r->string('date_from')->toString();
        $toIn = $r->string('date_to')->toString();
        try {
            $from = $fromIn ? Carbon::parse($fromIn)->startOfDay() : Carbon::now()->subDays(29)->startOfDay();
        } catch (\Throwable) {
            $from = Carbon::now()->subDays(29)->startOfDay();
        }
        try {
            $to = $toIn ? Carbon::parse($toIn)->endOfDay() : Carbon::now()->endOfDay();
        } catch (\Throwable) {
            $to = Carbon::now()->endOfDay();
        }
        if ($to->lt($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    private function pct(float|int $curr, float|int $prev): float
    {
        if ($prev == 0) {
            return $curr == 0 ? 0.0 : 100.0;
        }

        return round((($curr - $prev) / $prev) * 100, 2);
    }

    /** Single aggregate query. */
    private function overview(Carbon $from, Carbon $to): array
    {
        $row = $this->baseRevenueQuery($from, $to)
            ->selectRaw('COALESCE(SUM(total_price),0) as revenue, COALESCE(SUM(commission_amount),0) as commission, COUNT(*) as c')
            ->first();

        $revenue = (int) ($row->revenue ?? 0);
        $commission = (int) ($row->commission ?? 0);
        $count = (int) ($row->c ?? 0);

        return [
            'revenue' => $revenue,
            'commission' => $commission,
            'count' => $count,
            'avg' => $count > 0 ? (int) round($revenue / $count) : 0,
        ];
    }

    private function baseRevenueQuery(Carbon $from, Carbon $to): Builder
    {
        return Booking::query()
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereBetween('booking_date', [$from->toDateString(), $to->toDateString()]);
    }

    /** @return array<int, array{bucket:string,label_ar:string,label_en:string,revenue:int,bookings:int}> */
    private function trend(Carbon $from, Carbon $to, string $granularity): array
    {
        [$rawExpr, $format] = match ($granularity) {
            'day' => ['DATE(booking_date)', 'Y-m-d'],
            'week' => ["DATE_FORMAT(booking_date, '%x-%v')", 'o-W'],
            default => ["DATE_FORMAT(booking_date, '%Y-%m')", 'Y-m'],
        };

        $rows = $this->baseRevenueQuery($from, $to)
            ->selectRaw("{$rawExpr} as bucket, COALESCE(SUM(total_price),0) as revenue, COUNT(*) as bookings")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->keyBy('bucket');

        $out = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $key = match ($granularity) {
                'day' => $cursor->format('Y-m-d'),
                'week' => $cursor->format('o-W'),
                default => $cursor->format('Y-m'),
            };
            $row = $rows->get($key);
            $out[] = [
                'bucket' => $key,
                'label_ar' => $this->bucketLabel($cursor, $granularity, 'ar'),
                'label_en' => $this->bucketLabel($cursor, $granularity, 'en'),
                'revenue' => (int) ($row->revenue ?? 0),
                'bookings' => (int) ($row->bookings ?? 0),
            ];
            match ($granularity) {
                'day' => $cursor->addDay(),
                'week' => $cursor->addWeek(),
                default => $cursor->addMonth(),
            };
        }

        return $out;
    }

    private function bucketLabel(Carbon $c, string $granularity, string $locale): string
    {
        return match ($granularity) {
            'day' => $c->locale($locale)->translatedFormat('d M'),
            'week' => 'W'.$c->isoWeek,
            default => $c->locale($locale)->translatedFormat('M Y'),
        };
    }

    /** @return array<int, array<string,mixed>> */
    private function byVenue(Carbon $from, Carbon $to, int $totalRevenue): array
    {
        $rows = DB::table('bookings')
            ->join('venues', 'bookings.venue_id', '=', 'venues.id')
            ->leftJoin('clubs', 'venues.club_id', '=', 'clubs.id')
            ->leftJoin('cities', 'clubs.city_id', '=', 'cities.id')
            ->whereIn('bookings.status', ['confirmed', 'completed'])
            ->whereBetween('bookings.booking_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('venues.id', 'venues.name', 'venues.slug', 'clubs.id', 'clubs.name', 'cities.name', 'cities.name_ar')
            ->selectRaw('venues.id, venues.slug as venue_slug, venues.name as venue_name, clubs.id as club_id, clubs.name as club_name, cities.name as city_name, cities.name_ar as city_name_ar,
                COUNT(*) as bookings_count, COALESCE(SUM(bookings.total_price),0) as total_revenue')
            ->orderByDesc('total_revenue')
            ->limit(20)
            ->get();

        return $rows->map(fn ($r) => [
            'id' => (int) $r->id,
            'slug' => (string) $r->venue_slug,
            'name' => $this->decodeJson($r->venue_name),
            'club_id' => $r->club_id ? (int) $r->club_id : null,
            'club_name' => $this->decodeJson($r->club_name),
            'city_name' => $r->city_name,
            'city_name_ar' => $r->city_name_ar,
            'bookings_count' => (int) $r->bookings_count,
            'total_revenue' => (int) $r->total_revenue,
            'avg_booking_value' => $r->bookings_count > 0 ? (int) round($r->total_revenue / $r->bookings_count) : 0,
            'percentage' => $totalRevenue > 0 ? round(($r->total_revenue / $totalRevenue) * 100, 2) : 0,
        ])->all();
    }

    /** @return array<int, array<string,mixed>> */
    private function byClub(Carbon $from, Carbon $to, int $totalRevenue): array
    {
        $rows = DB::table('bookings')
            ->join('venues', 'bookings.venue_id', '=', 'venues.id')
            ->join('clubs', 'venues.club_id', '=', 'clubs.id')
            ->whereIn('bookings.status', ['confirmed', 'completed'])
            ->whereBetween('bookings.booking_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('clubs.id', 'clubs.name', 'clubs.slug')
            ->selectRaw('clubs.id, clubs.slug as club_slug, clubs.name as club_name,
                COUNT(DISTINCT venues.id) as venues_count,
                COUNT(*) as bookings_count,
                COALESCE(SUM(bookings.total_price),0) as total_revenue,
                COALESCE(SUM(bookings.commission_amount),0) as total_commission,
                COALESCE(SUM(bookings.club_payout_amount),0) as total_net')
            ->orderByDesc('total_revenue')
            ->limit(20)
            ->get();

        return $rows->map(fn ($r) => [
            'id' => (int) $r->id,
            'slug' => (string) $r->club_slug,
            'name' => $this->decodeJson($r->club_name),
            'venues_count' => (int) $r->venues_count,
            'bookings_count' => (int) $r->bookings_count,
            'total_revenue' => (int) $r->total_revenue,
            'commission' => (int) $r->total_commission,
            'net_amount' => (int) $r->total_net,
            'percentage' => $totalRevenue > 0 ? round(($r->total_revenue / $totalRevenue) * 100, 2) : 0,
        ])->all();
    }

    /** @return array<int, array<string,mixed>> */
    private function byCity(Carbon $from, Carbon $to, int $totalRevenue): array
    {
        $rows = DB::table('bookings')
            ->join('venues', 'bookings.venue_id', '=', 'venues.id')
            ->join('clubs', 'venues.club_id', '=', 'clubs.id')
            ->join('cities', 'clubs.city_id', '=', 'cities.id')
            ->whereIn('bookings.status', ['confirmed', 'completed'])
            ->whereBetween('bookings.booking_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('cities.id', 'cities.name', 'cities.name_ar')
            ->selectRaw('cities.id, cities.name, cities.name_ar,
                COUNT(DISTINCT venues.id) as venues_count,
                COUNT(*) as bookings_count,
                COALESCE(SUM(bookings.total_price),0) as total_revenue')
            ->orderByDesc('total_revenue')
            ->get();

        return $rows->map(fn ($r) => [
            'id' => (int) $r->id,
            'name' => $r->name,
            'name_ar' => $r->name_ar,
            'venues_count' => (int) $r->venues_count,
            'bookings_count' => (int) $r->bookings_count,
            'total_revenue' => (int) $r->total_revenue,
            'percentage' => $totalRevenue > 0 ? round(($r->total_revenue / $totalRevenue) * 100, 2) : 0,
        ])->all();
    }

    /** @return array<int, array<string,mixed>> */
    private function bySport(Carbon $from, Carbon $to, int $totalRevenue): array
    {
        $rows = DB::table('bookings')
            ->join('venues', 'bookings.venue_id', '=', 'venues.id')
            ->join('venue_categories', 'venues.category_id', '=', 'venue_categories.id')
            ->whereIn('bookings.status', ['confirmed', 'completed'])
            ->whereBetween('bookings.booking_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('venue_categories.id', 'venue_categories.name')
            ->selectRaw('venue_categories.id, venue_categories.name,
                COUNT(*) as bookings_count,
                COALESCE(SUM(bookings.total_price),0) as total_revenue')
            ->orderByDesc('total_revenue')
            ->get();

        return $rows->map(fn ($r) => [
            'id' => (int) $r->id,
            'name' => $this->decodeJson($r->name),
            'bookings_count' => (int) $r->bookings_count,
            'total_revenue' => (int) $r->total_revenue,
            'avg_booking_value' => $r->bookings_count > 0 ? (int) round($r->total_revenue / $r->bookings_count) : 0,
            'percentage' => $totalRevenue > 0 ? round(($r->total_revenue / $totalRevenue) * 100, 2) : 0,
        ])->all();
    }

    /** @return array<int, array<string,mixed>> */
    private function paymentMethods(Carbon $from, Carbon $to, int $totalRevenue): array
    {
        $rows = DB::table('payments')
            ->join('bookings', 'payments.booking_id', '=', 'bookings.id')
            ->whereIn('bookings.status', ['confirmed', 'completed'])
            ->whereBetween('bookings.booking_date', [$from->toDateString(), $to->toDateString()])
            ->where('payments.status', 'completed')
            ->groupBy('payments.provider')
            ->selectRaw('payments.provider, COUNT(*) as bookings_count, COALESCE(SUM(payments.amount),0) as total_amount')
            ->orderByDesc('total_amount')
            ->get();

        return $rows->map(fn ($r) => [
            'provider' => (string) $r->provider,
            'bookings_count' => (int) $r->bookings_count,
            'total_amount' => (int) $r->total_amount,
            'percentage' => $totalRevenue > 0 ? round(($r->total_amount / $totalRevenue) * 100, 2) : 0,
        ])->all();
    }

    /** @return array<int, array{day:int, hour:int, revenue:int, bookings:int}> */
    private function timeHeatmap(Carbon $from, Carbon $to): array
    {
        // MySQL DAYOFWEEK: Sunday=1, Monday=2, ..., Saturday=7.
        // We remap to Saturday-first index 0..6 for UI: Sat=0, Sun=1, Mon=2, ..., Fri=6.
        $rows = $this->baseRevenueQuery($from, $to)
            ->selectRaw('DAYOFWEEK(booking_date) as dow, HOUR(start_time) as hour, COALESCE(SUM(total_price),0) as revenue, COUNT(*) as bookings')
            ->groupBy('dow', 'hour')
            ->get();

        return $rows->map(fn ($r) => [
            // Remap: SQL 7 (Sat) -> 0; 1 (Sun) -> 1; 2 (Mon) -> 2; ... 6 (Fri) -> 6
            'day' => ((int) $r->dow) === 7 ? 0 : (int) $r->dow,
            'hour' => (int) $r->hour,
            'revenue' => (int) $r->revenue,
            'bookings' => (int) $r->bookings,
        ])->values()->all();
    }

    /**
     * Spatie Translatable stores JSON; selecting via DB::table gives the raw string.
     * Decode to an array so the frontend can pick the right locale.
     *
     * @return array<string, string>
     */
    private function decodeJson(?string $raw): array
    {
        if ($raw === null) {
            return ['ar' => '', 'en' => ''];
        }
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return ['ar' => (string) ($decoded['ar'] ?? ''), 'en' => (string) ($decoded['en'] ?? '')];
        }

        return ['ar' => $raw, 'en' => $raw];
    }

    // ---------- Booking analytics helpers ----------

    /** @return array{total:int, confirmed:int, completed:int, cancelled:int, avg_duration_minutes:float} */
    private function bookingsOverview(Carbon $from, Carbon $to): array
    {
        $row = Booking::query()
            ->whereBetween('booking_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "confirmed" THEN 1 ELSE 0 END) as confirmed,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled,
                COALESCE(AVG(CASE WHEN status IN ("confirmed","completed") THEN duration_minutes END), 0) as avg_dur
            ')
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'confirmed' => (int) ($row->confirmed ?? 0),
            'completed' => (int) ($row->completed ?? 0),
            'cancelled' => (int) ($row->cancelled ?? 0),
            'avg_duration_minutes' => (float) ($row->avg_dur ?? 0),
        ];
    }

    /** @return array<int, array{bucket:string,label_ar:string,label_en:string,confirmed:int,completed:int,cancelled:int,total:int}> */
    private function bookingsTrend(Carbon $from, Carbon $to, string $granularity): array
    {
        [$rawExpr] = match ($granularity) {
            'day' => ['DATE(booking_date)'],
            'week' => ["DATE_FORMAT(booking_date, '%x-%v')"],
            default => ["DATE_FORMAT(booking_date, '%Y-%m')"],
        };

        $rows = Booking::query()
            ->whereBetween('booking_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw("{$rawExpr} as bucket,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                COUNT(*) as total")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->keyBy('bucket');

        $out = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $key = match ($granularity) {
                'day' => $cursor->format('Y-m-d'),
                'week' => $cursor->format('o-W'),
                default => $cursor->format('Y-m'),
            };
            $row = $rows->get($key);
            $out[] = [
                'bucket' => $key,
                'label_ar' => $this->bucketLabel($cursor, $granularity, 'ar'),
                'label_en' => $this->bucketLabel($cursor, $granularity, 'en'),
                'confirmed' => (int) ($row->confirmed ?? 0),
                'completed' => (int) ($row->completed ?? 0),
                'cancelled' => (int) ($row->cancelled ?? 0),
                'total' => (int) ($row->total ?? 0),
            ];
            match ($granularity) {
                'day' => $cursor->addDay(),
                'week' => $cursor->addWeek(),
                default => $cursor->addMonth(),
            };
        }

        return $out;
    }

    /** @return array<int, array{hour:int,bookings:int}> */
    private function bookingsPeakHours(Carbon $from, Carbon $to): array
    {
        $rows = Booking::query()
            ->whereBetween('booking_date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('status', ['confirmed', 'completed'])
            ->selectRaw('HOUR(start_time) as hour, COUNT(*) as bookings')
            ->groupBy('hour')
            ->pluck('bookings', 'hour')
            ->all();

        $out = [];
        for ($h = 8; $h <= 23; $h++) {
            $out[] = ['hour' => $h, 'bookings' => (int) ($rows[$h] ?? 0)];
        }

        return $out;
    }

    /** @return array<int, array<string,mixed>> */
    private function popularVenues(Carbon $from, Carbon $to, int $days): array
    {
        // Occupancy heuristic: available hours ≈ 12h/day × days (matches VenueService fallback
        // in fresh DBs; swap to a per-venue opening_hours sum if needed).
        $availableHours = max(1, $days * 12);

        $rows = DB::table('bookings')
            ->join('venues', 'bookings.venue_id', '=', 'venues.id')
            ->leftJoin('clubs', 'venues.club_id', '=', 'clubs.id')
            ->leftJoin('cities', 'clubs.city_id', '=', 'cities.id')
            ->whereIn('bookings.status', ['confirmed', 'completed'])
            ->whereBetween('bookings.booking_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('venues.id', 'venues.name', 'venues.slug', 'venues.avg_rating', 'clubs.id', 'clubs.name', 'cities.name', 'cities.name_ar')
            ->selectRaw('venues.id, venues.slug as venue_slug, venues.name as venue_name, venues.avg_rating,
                clubs.id as club_id, clubs.name as club_name,
                cities.name as city_name, cities.name_ar as city_name_ar,
                COUNT(*) as bookings_count,
                COALESCE(SUM(bookings.duration_minutes), 0) / 60 as total_hours')
            ->orderByDesc('bookings_count')
            ->limit(20)
            ->get();

        return $rows->map(fn ($r) => [
            'id' => (int) $r->id,
            'slug' => (string) $r->venue_slug,
            'name' => $this->decodeJson($r->venue_name),
            'club_id' => $r->club_id ? (int) $r->club_id : null,
            'club_name' => $this->decodeJson($r->club_name),
            'city_name' => $r->city_name,
            'city_name_ar' => $r->city_name_ar,
            'bookings_count' => (int) $r->bookings_count,
            'avg_rating' => $r->avg_rating !== null ? round((float) $r->avg_rating, 1) : null,
            'occupancy_rate' => round(min(100, ((float) $r->total_hours / $availableHours) * 100), 1),
        ])->all();
    }

    /** @return array<int, array<string,mixed>> */
    private function bookingsBySport(Carbon $from, Carbon $to, int $totalBookings): array
    {
        $rows = DB::table('bookings')
            ->join('venues', 'bookings.venue_id', '=', 'venues.id')
            ->join('venue_categories', 'venues.category_id', '=', 'venue_categories.id')
            ->whereIn('bookings.status', ['confirmed', 'completed'])
            ->whereBetween('bookings.booking_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('venue_categories.id', 'venue_categories.name')
            ->selectRaw('venue_categories.id, venue_categories.name,
                COUNT(*) as bookings_count,
                COALESCE(AVG(bookings.duration_minutes),0) as avg_duration_min,
                COALESCE(AVG(bookings.total_price),0) as avg_price')
            ->orderByDesc('bookings_count')
            ->get();

        return $rows->map(fn ($r) => [
            'id' => (int) $r->id,
            'name' => $this->decodeJson($r->name),
            'bookings_count' => (int) $r->bookings_count,
            'percentage' => $totalBookings > 0 ? round(($r->bookings_count / $totalBookings) * 100, 2) : 0,
            'avg_duration_hours' => round((float) $r->avg_duration_min / 60, 2),
            'avg_price' => (int) round((float) $r->avg_price),
        ])->all();
    }

    /** @return array<int, array{key:string,count:int}> */
    private function durationBuckets(Carbon $from, Carbon $to): array
    {
        $row = Booking::query()
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereBetween('booking_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('
                SUM(CASE WHEN duration_minutes < 60 THEN 1 ELSE 0 END) as under_1h,
                SUM(CASE WHEN duration_minutes BETWEEN 60 AND 119 THEN 1 ELSE 0 END) as h_1_2,
                SUM(CASE WHEN duration_minutes BETWEEN 120 AND 179 THEN 1 ELSE 0 END) as h_2_3,
                SUM(CASE WHEN duration_minutes BETWEEN 180 AND 239 THEN 1 ELSE 0 END) as h_3_4,
                SUM(CASE WHEN duration_minutes >= 240 THEN 1 ELSE 0 END) as over_4h')
            ->first();

        return [
            ['key' => 'under1h', 'count' => (int) ($row->under_1h ?? 0)],
            ['key' => '1to2h', 'count' => (int) ($row->h_1_2 ?? 0)],
            ['key' => '2to3h', 'count' => (int) ($row->h_2_3 ?? 0)],
            ['key' => '3to4h', 'count' => (int) ($row->h_3_4 ?? 0)],
            ['key' => '4plus', 'count' => (int) ($row->over_4h ?? 0)],
        ];
    }

    /** @return array<int, array{reason:string,count:int,percentage:float}> */
    private function cancellationReasons(Carbon $from, Carbon $to, int $totalCancelled): array
    {
        $rows = Booking::query()
            ->where('status', 'cancelled')
            ->whereBetween('booking_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('COALESCE(NULLIF(cancellation_reason, ""), "غير محدد") as reason, COUNT(*) as c')
            ->groupBy('reason')
            ->orderByDesc('c')
            ->get();

        return $rows->map(fn ($r) => [
            'reason' => (string) $r->reason,
            'count' => (int) $r->c,
            'percentage' => $totalCancelled > 0 ? round(($r->c / $totalCancelled) * 100, 2) : 0,
        ])->all();
    }

    /** @return array<int, array{key:string,count:int}> */
    private function cancellationTiming(Carbon $from, Carbon $to): array
    {
        $row = Booking::query()
            ->where('status', 'cancelled')
            ->whereBetween('booking_date', [$from->toDateString(), $to->toDateString()])
            ->whereNotNull('cancelled_at')
            ->selectRaw('
                SUM(CASE WHEN DATEDIFF(booking_date, DATE(cancelled_at)) = 0 THEN 1 ELSE 0 END) as same_day,
                SUM(CASE WHEN DATEDIFF(booking_date, DATE(cancelled_at)) = 1 THEN 1 ELSE 0 END) as day_1,
                SUM(CASE WHEN DATEDIFF(booking_date, DATE(cancelled_at)) BETWEEN 2 AND 3 THEN 1 ELSE 0 END) as d_2_3,
                SUM(CASE WHEN DATEDIFF(booking_date, DATE(cancelled_at)) BETWEEN 4 AND 7 THEN 1 ELSE 0 END) as d_4_7,
                SUM(CASE WHEN DATEDIFF(booking_date, DATE(cancelled_at)) >= 8 THEN 1 ELSE 0 END) as d_8p')
            ->first();

        return [
            ['key' => 'sameDay', 'count' => (int) ($row->same_day ?? 0)],
            ['key' => '1DayBefore', 'count' => (int) ($row->day_1 ?? 0)],
            ['key' => '2to3Days', 'count' => (int) ($row->d_2_3 ?? 0)],
            ['key' => '4to7Days', 'count' => (int) ($row->d_4_7 ?? 0)],
            ['key' => '8PlusDays', 'count' => (int) ($row->d_8p ?? 0)],
        ];
    }

    /** @return array{buckets: array<int,array{key:string,count:int}>, avg_days: float} */
    private function advanceWindow(Carbon $from, Carbon $to): array
    {
        $row = Booking::query()
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereBetween('booking_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('
                SUM(CASE WHEN DATEDIFF(booking_date, DATE(created_at)) <= 0 THEN 1 ELSE 0 END) as same_day,
                SUM(CASE WHEN DATEDIFF(booking_date, DATE(created_at)) = 1 THEN 1 ELSE 0 END) as d_1,
                SUM(CASE WHEN DATEDIFF(booking_date, DATE(created_at)) BETWEEN 2 AND 3 THEN 1 ELSE 0 END) as d_2_3,
                SUM(CASE WHEN DATEDIFF(booking_date, DATE(created_at)) BETWEEN 4 AND 7 THEN 1 ELSE 0 END) as d_4_7,
                SUM(CASE WHEN DATEDIFF(booking_date, DATE(created_at)) BETWEEN 8 AND 14 THEN 1 ELSE 0 END) as d_8_14,
                SUM(CASE WHEN DATEDIFF(booking_date, DATE(created_at)) BETWEEN 15 AND 30 THEN 1 ELSE 0 END) as d_15_30,
                SUM(CASE WHEN DATEDIFF(booking_date, DATE(created_at)) > 30 THEN 1 ELSE 0 END) as d_30p,
                COALESCE(AVG(GREATEST(DATEDIFF(booking_date, DATE(created_at)), 0)), 0) as avg_days')
            ->first();

        return [
            'buckets' => [
                ['key' => 'sameDay', 'count' => (int) ($row->same_day ?? 0)],
                ['key' => '1Day', 'count' => (int) ($row->d_1 ?? 0)],
                ['key' => '2to3Days', 'count' => (int) ($row->d_2_3 ?? 0)],
                ['key' => '4to7Days', 'count' => (int) ($row->d_4_7 ?? 0)],
                ['key' => '8to14Days', 'count' => (int) ($row->d_8_14 ?? 0)],
                ['key' => '15to30Days', 'count' => (int) ($row->d_15_30 ?? 0)],
                ['key' => '30PlusDays', 'count' => (int) ($row->d_30p ?? 0)],
            ],
            'avg_days' => round((float) ($row->avg_days ?? 0), 1),
        ];
    }

    // ---------- Player analytics helpers ----------

    /** Query all users with the 'player' role (via Spatie pivot). */
    private function playerUserQuery(): \Illuminate\Database\Query\Builder
    {
        return DB::table('users')
            ->join('model_has_roles', function ($j) {
                $j->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', '=', User::class);
            })
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('roles.name', UserRole::Player->value)
            ->whereNull('users.deleted_at');
    }

    /** @return Collection<int,int> */
    private function activePlayerIds(Carbon $from, Carbon $to): Collection
    {
        return Booking::query()
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereBetween('booking_date', [$from->toDateString(), $to->toDateString()])
            ->distinct()
            ->pluck('user_id');
    }

    /** @return array<int, array{step:string, count:int}> */
    private function buildFunnel(int $totalPlayers): array
    {
        // Count distinct players who emitted each event type.
        $eventCounts = DB::table('player_events')
            ->selectRaw('event_name, COUNT(DISTINCT user_id) as c')
            ->whereNotNull('user_id')
            ->whereIn('event_name', ['venue_viewed', 'venue_searched', 'booking_initiated'])
            ->groupBy('event_name')
            ->pluck('c', 'event_name')
            ->all();

        // Players with ≥1 completed payment = distinct user_ids on confirmed/completed bookings.
        $paidPlayerIds = Booking::query()
            ->whereIn('status', ['confirmed', 'completed'])
            ->distinct()
            ->pluck('user_id');
        $completedCount = $paidPlayerIds->count();

        // Players with ≥2 completed/confirmed bookings = rebooked.
        $rebookedCount = (int) Booking::query()
            ->whereIn('status', ['confirmed', 'completed'])
            ->selectRaw('COUNT(*) as c')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) >= 2')
            ->get()
            ->count();

        return [
            ['step' => 'registered', 'count' => $totalPlayers],
            ['step' => 'viewed_venues', 'count' => (int) ($eventCounts['venue_viewed'] ?? 0)],
            ['step' => 'searched', 'count' => (int) ($eventCounts['venue_searched'] ?? 0)],
            ['step' => 'initiated', 'count' => (int) ($eventCounts['booking_initiated'] ?? 0)],
            ['step' => 'completed', 'count' => $completedCount],
            ['step' => 'rebooked', 'count' => $rebookedCount],
        ];
    }

    /** @param  array<int, array{step:string,count:int}>  $funnel
     *  @return array<int, array{stage:string,count:int}> */
    private function buildDropoff(array $funnel): array
    {
        $pairs = [
            'registered_never_viewed' => [0, 1],
            'viewed_never_searched' => [1, 2],
            'searched_never_initiated' => [2, 3],
            'initiated_never_completed' => [3, 4],
            'completed_never_rebooked' => [4, 5],
        ];

        $out = [];
        foreach ($pairs as $stage => [$a, $b]) {
            $diff = max(0, ($funnel[$a]['count'] ?? 0) - ($funnel[$b]['count'] ?? 0));
            $out[] = ['stage' => $stage, 'count' => $diff];
        }

        return $out;
    }

    /** @return array<int, array<string,mixed>> */
    private function buildSegmentation(int $totalPlayers): array
    {
        $rows = DB::table('users')
            ->join('model_has_roles', function ($j) {
                $j->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', '=', User::class);
            })
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('roles.name', UserRole::Player->value)
            ->whereNull('users.deleted_at')
            ->leftJoin('bookings', function ($j) {
                $j->on('bookings.user_id', '=', 'users.id')
                    ->whereIn('bookings.status', ['confirmed', 'completed']);
            })
            ->groupBy('users.id')
            ->selectRaw('users.id, COUNT(bookings.id) as bookings_count, COALESCE(SUM(bookings.total_price),0) as revenue')
            ->get();

        $segments = [
            'inactive' => ['player_count' => 0, 'total_bookings' => 0, 'total_revenue' => 0],
            'new' => ['player_count' => 0, 'total_bookings' => 0, 'total_revenue' => 0],
            'occasional' => ['player_count' => 0, 'total_bookings' => 0, 'total_revenue' => 0],
            'regular' => ['player_count' => 0, 'total_bookings' => 0, 'total_revenue' => 0],
            'loyal' => ['player_count' => 0, 'total_bookings' => 0, 'total_revenue' => 0],
        ];

        foreach ($rows as $r) {
            $n = (int) $r->bookings_count;
            $seg = $n === 0 ? 'inactive' : ($n === 1 ? 'new' : ($n <= 5 ? 'occasional' : ($n <= 15 ? 'regular' : 'loyal')));
            $segments[$seg]['player_count']++;
            $segments[$seg]['total_bookings'] += $n;
            $segments[$seg]['total_revenue'] += (int) $r->revenue;
        }

        $out = [];
        foreach ($segments as $key => $data) {
            $out[] = [
                'segment' => $key,
                'player_count' => $data['player_count'],
                'percentage' => $totalPlayers > 0 ? round(($data['player_count'] / $totalPlayers) * 100, 2) : 0,
                'total_bookings' => $data['total_bookings'],
                'total_revenue' => $data['total_revenue'],
                'avg_booking_value' => $data['total_bookings'] > 0 ? (int) round($data['total_revenue'] / $data['total_bookings']) : 0,
            ];
        }

        return $out;
    }

    /** @return array<int, array<string,mixed>> */
    private function buildSpendingTiers(int $totalPlayers): array
    {
        $rows = DB::table('users')
            ->join('model_has_roles', function ($j) {
                $j->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', '=', User::class);
            })
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('roles.name', UserRole::Player->value)
            ->whereNull('users.deleted_at')
            ->leftJoin('bookings', function ($j) {
                $j->on('bookings.user_id', '=', 'users.id')
                    ->whereIn('bookings.status', ['confirmed', 'completed']);
            })
            ->groupBy('users.id')
            ->selectRaw('users.id, COALESCE(SUM(bookings.total_price),0) as spent')
            ->get();

        $tiers = [
            'under_100k' => ['player_count' => 0, 'total_revenue' => 0],
            '100k_500k' => ['player_count' => 0, 'total_revenue' => 0],
            '500k_1m' => ['player_count' => 0, 'total_revenue' => 0],
            'over_1m' => ['player_count' => 0, 'total_revenue' => 0],
        ];

        $totalRevenueAll = 0;
        foreach ($rows as $r) {
            $s = (int) $r->spent;
            $totalRevenueAll += $s;
            $key = $s < 100000 ? 'under_100k' : ($s < 500000 ? '100k_500k' : ($s < 1000000 ? '500k_1m' : 'over_1m'));
            $tiers[$key]['player_count']++;
            $tiers[$key]['total_revenue'] += $s;
        }

        $out = [];
        foreach ($tiers as $key => $data) {
            $out[] = [
                'tier' => $key,
                'player_count' => $data['player_count'],
                'percentage' => $totalPlayers > 0 ? round(($data['player_count'] / $totalPlayers) * 100, 2) : 0,
                'total_revenue' => $data['total_revenue'],
                'revenue_percentage' => $totalRevenueAll > 0 ? round(($data['total_revenue'] / $totalRevenueAll) * 100, 2) : 0,
                'avg_spending' => $data['player_count'] > 0 ? (int) round($data['total_revenue'] / $data['player_count']) : 0,
            ];
        }

        return $out;
    }

    /**
     * Retention cohort matrix for the last 6 months.
     * Single query: pivots player-month-offset → active counts.
     *
     * @return array<int, array<string,mixed>>
     */
    private function buildCohorts(): array
    {
        $anchor = Carbon::now()->startOfMonth()->subMonths(5);

        // Cohort sizes: players who joined in each month.
        $sizes = DB::table('users')
            ->join('model_has_roles', function ($j) {
                $j->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', '=', User::class);
            })
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('roles.name', UserRole::Player->value)
            ->whereNull('users.deleted_at')
            ->where('users.created_at', '>=', $anchor->toDateTimeString())
            ->selectRaw("DATE_FORMAT(users.created_at, '%Y-%m') as cohort, COUNT(*) as size")
            ->groupBy('cohort')
            ->pluck('size', 'cohort')
            ->all();

        // Active cells: for each booking, find the player's cohort month and compute offset.
        $cells = DB::table('bookings')
            ->join('users', 'bookings.user_id', '=', 'users.id')
            ->join('model_has_roles', function ($j) {
                $j->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', '=', User::class);
            })
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('roles.name', UserRole::Player->value)
            ->whereIn('bookings.status', ['confirmed', 'completed'])
            ->where('users.created_at', '>=', $anchor->toDateTimeString())
            ->selectRaw("
                DATE_FORMAT(users.created_at, '%Y-%m') as cohort,
                TIMESTAMPDIFF(MONTH, DATE_FORMAT(users.created_at, '%Y-%m-01'), DATE_FORMAT(bookings.booking_date, '%Y-%m-01')) as offset,
                COUNT(DISTINCT users.id) as active
            ")
            ->groupBy('cohort', 'offset')
            ->get();

        $matrix = [];
        foreach ($cells as $c) {
            if ($c->offset < 0 || $c->offset > 6) {
                continue;
            }
            $matrix[$c->cohort][$c->offset] = (int) $c->active;
        }

        $now = Carbon::now()->startOfMonth();
        $out = [];
        for ($i = 0; $i < 6; $i++) {
            $month = $anchor->copy()->addMonths($i);
            $key = $month->format('Y-m');
            $size = (int) ($sizes[$key] ?? 0);
            if ($size === 0) {
                continue;
            }
            $row = [
                'month' => $key,
                'size' => $size,
                'month_0' => 100.0,
            ];
            for ($m = 1; $m <= 6; $m++) {
                $target = $month->copy()->addMonths($m);
                if ($target->gt($now)) {
                    $row["month_{$m}"] = null;

                    continue;
                }
                $active = (int) ($matrix[$key][$m] ?? 0);
                $row["month_{$m}"] = round(($active / $size) * 100, 1);
            }
            $out[] = $row;
        }

        return $out;
    }

    /** @return array<int, array<string,mixed>> */
    private function fetchEvents(Request $request): array
    {
        $eventName = $request->string('event_name')->toString();
        [$from, $to] = $this->resolveRange($request);

        $rows = DB::table('player_events')
            ->leftJoin('users', 'player_events.user_id', '=', 'users.id')
            ->whereBetween('player_events.occurred_at', [$from->toDateTimeString(), $to->toDateTimeString()])
            ->when($eventName, fn ($q, $e) => $q->where('player_events.event_name', $e))
            ->select([
                'player_events.id',
                'users.id as player_id',
                'users.name as player_name',
                'player_events.event_name',
                'player_events.properties',
                'player_events.occurred_at',
                'player_events.session_id',
                'player_events.anonymous_id',
            ])
            ->orderByDesc('player_events.occurred_at')
            ->limit(100)
            ->get();

        return $rows->map(function ($r) {
            $props = $r->properties ? json_decode($r->properties, true) : null;

            return [
                'id' => (int) $r->id,
                'player_id' => $r->player_id ? (int) $r->player_id : null,
                'player_name' => $r->player_name,
                'event_name' => (string) $r->event_name,
                'properties' => is_array($props) ? $props : null,
                'occurred_at' => $r->occurred_at,
                'session_id' => $r->session_id,
                'anonymous_id' => $r->anonymous_id,
            ];
        })->all();
    }

    /** @return array<int, string> */
    private function distinctEventNames(): array
    {
        return DB::table('player_events')
            ->select('event_name')
            ->distinct()
            ->orderBy('event_name')
            ->pluck('event_name')
            ->filter()
            ->values()
            ->all();
    }
}
