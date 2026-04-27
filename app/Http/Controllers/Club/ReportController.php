<?php

namespace App\Http\Controllers\Club;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Club;
use App\Models\Settlement;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const TEMPLATES = ['revenue', 'settlements', 'bookings'];

    private const GROUP_BY = ['day', 'week', 'month', 'venue'];

    public function index(Request $request): Response|RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $filters = $this->extractFilters($request);
        $preview = $this->buildDataset($club, $filters);
        $locale = app()->getLocale();

        return Inertia::render('Club/Finances/Reports/Index', [
            'template' => $filters['template'],
            'filters' => [
                'date_from' => $filters['date_from'],
                'date_to' => $filters['date_to'],
                'venue_slug' => $filters['venue_slug'],
                'group_by' => $filters['group_by'],
            ],
            'previewData' => $preview,
            'venues' => $club->venues()
                ->orderBy('name->ar')
                ->get(['id', 'slug', 'name'])
                ->map(fn (Venue $v) => [
                    'slug' => $v->slug,
                    'name' => $v->getTranslation('name', $locale) ?: $v->getTranslation('name', 'ar'),
                ]),
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse|RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $filters = $this->extractFilters($request);
        $rows = $this->buildDataset($club, $filters);

        $template = $filters['template'];
        $filename = "{$template}_report_".now()->format('Y-m-d_His').'.csv';

        $callback = function () use ($rows, $template): void {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM so Excel opens Arabic correctly.
            fwrite($out, "\xEF\xBB\xBF");

            match ($template) {
                'revenue' => $this->writeRevenueCsv($out, $rows),
                'settlements' => $this->writeSettlementsCsv($out, $rows),
                'bookings' => $this->writeBookingsCsv($out, $rows),
                default => null,
            };

            fclose($out);
        };

        return response()->stream($callback, HttpResponse::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function exportPdf(Request $request): RedirectResponse
    {
        return back()
            ->with('flash_key', 'pdfExportComingSoon')
            ->with('flash_type', 'info');
    }

    public function exportExcel(Request $request): RedirectResponse
    {
        return back()
            ->with('flash_key', 'excelExportComingSoon')
            ->with('flash_type', 'info');
    }

    // ---------- dataset builders ----------

    /**
     * @return array{template:string,date_from:string,date_to:string,venue_slug:?string,group_by:string}
     */
    private function extractFilters(Request $request): array
    {
        $template = $request->string('template')->toString();
        if (! in_array($template, self::TEMPLATES, true)) {
            $template = 'revenue';
        }

        $groupBy = $request->string('group_by')->toString();
        if (! in_array($groupBy, self::GROUP_BY, true)) {
            $groupBy = 'day';
        }

        $dateFrom = $this->parseDate($request->string('date_from')->toString(), now()->subDays(30));
        $dateTo = $this->parseDate($request->string('date_to')->toString(), now());
        if ($dateTo->lt($dateFrom)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $venueSlug = $request->string('venue_slug')->toString() ?: null;

        return [
            'template' => $template,
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
            'venue_slug' => $venueSlug,
            'group_by' => $groupBy,
        ];
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
     * @return array<int, array<string, mixed>>
     */
    private function buildDataset(Club $club, array $filters): array
    {
        return match ($filters['template']) {
            'revenue' => $this->getRevenueData($club, $filters),
            'settlements' => $this->getSettlementsData($club, $filters),
            'bookings' => $this->getBookingsData($club, $filters),
            default => [],
        };
    }

    /** @return array<int, array<string, mixed>> */
    private function getRevenueData(Club $club, array $filters): array
    {
        $venueIds = $club->venues()->pluck('id');

        $query = Booking::query()
            ->whereIn('venue_id', $venueIds)
            ->where('status', BookingStatus::Completed->value)
            ->whereBetween('booking_date', [$filters['date_from'], $filters['date_to']]);

        if ($filters['venue_slug']) {
            $query->whereHas('venue', fn (Builder $q) => $q->where('slug', $filters['venue_slug']));
        }

        $locale = app()->getLocale();

        if ($filters['group_by'] === 'venue') {
            return $query
                ->with('venue:id,name')
                ->selectRaw('venue_id')
                ->selectRaw('COUNT(*) as bookings_count')
                ->selectRaw('COALESCE(SUM(total_price), 0) as revenue')
                ->selectRaw('COALESCE(SUM(commission_amount), 0) as commission')
                ->selectRaw('COALESCE(SUM(club_payout_amount), 0) as net')
                ->groupBy('venue_id')
                ->orderByDesc('revenue')
                ->get()
                ->map(fn ($row) => [
                    'period' => $row->venue?->getTranslation('name', $locale)
                        ?: $row->venue?->getTranslation('name', 'ar')
                        ?: '—',
                    'bookings_count' => (int) $row->bookings_count,
                    'revenue' => (int) $row->revenue,
                    'commission' => (int) $row->commission,
                    'net' => (int) $row->net,
                ])
                ->all();
        }

        $format = match ($filters['group_by']) {
            'week' => '%x-W%v',  // ISO year-week
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        return $query
            ->selectRaw("DATE_FORMAT(booking_date, '{$format}') as period")
            ->selectRaw('COUNT(*) as bookings_count')
            ->selectRaw('COALESCE(SUM(total_price), 0) as revenue')
            ->selectRaw('COALESCE(SUM(commission_amount), 0) as commission')
            ->selectRaw('COALESCE(SUM(club_payout_amount), 0) as net')
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(fn ($row) => [
                'period' => (string) $row->period,
                'bookings_count' => (int) $row->bookings_count,
                'revenue' => (int) $row->revenue,
                'commission' => (int) $row->commission,
                'net' => (int) $row->net,
            ])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function getSettlementsData(Club $club, array $filters): array
    {
        return Settlement::query()
            ->where('club_id', $club->id)
            ->whereBetween('created_at', [
                $filters['date_from'].' 00:00:00',
                $filters['date_to'].' 23:59:59',
            ])
            ->withCount('items as bookings_count')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Settlement $s) => [
                'settlement_number' => 'STL-'.str_pad((string) $s->id, 8, '0', STR_PAD_LEFT),
                'date' => $s->created_at?->toDateString(),
                'status' => $s->status,
                'bookings_count' => (int) ($s->bookings_count ?? 0),
                'gross_revenue' => (int) $s->total_venue_price,
                'commission' => (int) $s->total_commission,
                'net_payable' => (int) $s->net_payable,
                'paid_amount' => (int) $s->paid_amount,
            ])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function getBookingsData(Club $club, array $filters): array
    {
        $venueIds = $club->venues()->pluck('id');
        $locale = app()->getLocale();

        $query = Booking::query()
            ->with(['venue:id,slug,name', 'user:id,name'])
            ->whereIn('venue_id', $venueIds)
            ->whereBetween('booking_date', [$filters['date_from'], $filters['date_to']]);

        if ($filters['venue_slug']) {
            $query->whereHas('venue', fn (Builder $q) => $q->where('slug', $filters['venue_slug']));
        }

        return $query
            ->orderByDesc('booking_date')
            ->orderByDesc('start_time')
            ->get()
            ->map(fn (Booking $b) => [
                'booking_code' => $b->booking_code,
                'date' => $b->booking_date?->toDateString(),
                'venue' => $b->venue?->getTranslation('name', $locale)
                    ?: $b->venue?->getTranslation('name', 'ar')
                    ?: '—',
                'player' => $b->user?->name ?? '—',
                'status' => $b->status instanceof BookingStatus ? $b->status->value : $b->status,
                'total' => (int) $b->total_price,
                'commission' => (int) $b->commission_amount,
                'net' => (int) $b->club_payout_amount,
            ])
            ->all();
    }

    // ---------- CSV writers ----------

    /** @param resource $out */
    private function writeRevenueCsv($out, array $rows): void
    {
        fputcsv($out, ['الفترة', 'عدد الحجوزات', 'الإيرادات', 'العمولة', 'الصافي']);
        foreach ($rows as $row) {
            fputcsv($out, [
                $row['period'],
                $row['bookings_count'],
                $row['revenue'],
                $row['commission'],
                $row['net'],
            ]);
        }
    }

    /** @param resource $out */
    private function writeSettlementsCsv($out, array $rows): void
    {
        fputcsv($out, [
            'رقم التسوية', 'التاريخ', 'الحالة', 'عدد الحجوزات',
            'الإيرادات الإجمالية', 'العمولة', 'الصافي المستحق', 'المبلغ المدفوع',
        ]);
        foreach ($rows as $row) {
            fputcsv($out, [
                $row['settlement_number'],
                $row['date'],
                $row['status'],
                $row['bookings_count'],
                $row['gross_revenue'],
                $row['commission'],
                $row['net_payable'],
                $row['paid_amount'],
            ]);
        }
    }

    /** @param resource $out */
    private function writeBookingsCsv($out, array $rows): void
    {
        fputcsv($out, [
            'رمز الحجز', 'التاريخ', 'الملعب', 'اللاعب', 'الحالة',
            'المبلغ', 'العمولة', 'الصافي',
        ]);
        foreach ($rows as $row) {
            fputcsv($out, [
                $row['booking_code'],
                $row['date'],
                $row['venue'],
                $row['player'],
                $row['status'],
                $row['total'],
                $row['commission'],
                $row['net'],
            ]);
        }
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
