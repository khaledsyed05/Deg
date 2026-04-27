<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CommissionScope;
use App\Enums\CommissionType;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Club;
use App\Models\CommissionConfig;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class CommissionController extends Controller
{
    /** Default fallback rate when no global config exists. */
    private const FALLBACK_DEFAULT_RATE = 10;

    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'has_override' => $request->string('has_override')->toString(),
            'rate_min' => $request->input('rate_min'),
            'rate_max' => $request->input('rate_max'),
            'city_id' => $request->integer('city_id') ?: null,
        ];

        $globalConfig = CommissionConfig::query()
            ->where('scope', CommissionScope::Global)
            ->where('is_active', true)
            ->latest('effective_from')
            ->first();

        $defaultRate = $globalConfig
            ? ($globalConfig->commission_type === CommissionType::Percentage ? (int) $globalConfig->commission_value : null)
            : self::FALLBACK_DEFAULT_RATE;

        $overridesByClub = CommissionConfig::query()
            ->where('scope', CommissionScope::Club)
            ->where('is_active', true)
            ->whereNotNull('club_id')
            ->get()
            ->keyBy('club_id');

        $from = now()->subDays(30);
        $to = now();

        // Revenue for clubs WITHOUT an override (i.e. using platform default).
        $overriddenClubIds = $overridesByClub->keys()->all();
        $defaultRateRevenue = (int) Booking::query()
            ->join('venues', 'bookings.venue_id', '=', 'venues.id')
            ->whereIn('bookings.status', ['confirmed', 'completed'])
            ->whereBetween('bookings.booking_date', [$from->toDateString(), $to->toDateString()])
            ->when(! empty($overriddenClubIds), fn ($q) => $q->whereNotIn('venues.club_id', $overriddenClubIds))
            ->sum('bookings.total_price');

        $clubsUsingDefault = Club::query()
            ->when(! empty($overriddenClubIds), fn ($q) => $q->whereNotIn('id', $overriddenClubIds))
            ->count();

        $defaultCommission = $defaultRate !== null ? (int) round(($defaultRateRevenue * $defaultRate) / 100) : 0;

        // Change history from activity log.
        $changeHistory = Activity::query()
            ->where('log_name', 'commission')
            ->whereIn('description', ['platform_default_updated', 'club_override_set', 'club_override_removed'])
            ->with('causer:id,name', 'subject')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (Activity $a) => [
                'id' => $a->id,
                'description' => $a->description,
                'properties' => $a->properties,
                'causer' => $a->causer?->only(['id', 'name']),
                'created_at' => $a->created_at?->toIso8601String(),
            ]);

        $clubOverrides = $this->buildClubTable($filters, $defaultRate, $from, $to, $overridesByClub);

        $analytics = $this->analytics($from, $to);

        return Inertia::render('Admin/Settings/Commissions', [
            'platform_default' => [
                'rate' => $defaultRate,
                'clubs_using' => $clubsUsingDefault,
                'revenue_30d' => $defaultRateRevenue,
                'commission_30d' => $defaultCommission,
                'effective_from' => $globalConfig?->effective_from?->toDateString(),
                'note' => $globalConfig?->note,
            ],
            'change_history' => $changeHistory,
            'club_overrides' => $clubOverrides,
            'filters' => $filters,
            'analytics' => $analytics,
        ]);
    }

    public function updateDefaultRate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'rate' => ['required', 'integer', 'min:0', 'max:50'],
            'effective_from' => ['required', 'date'],
            'reason' => ['required', 'string', 'min:20', 'max:500'],
        ]);

        $current = CommissionConfig::query()
            ->where('scope', CommissionScope::Global)
            ->where('is_active', true)
            ->latest('effective_from')
            ->first();

        $oldRate = $current?->commission_type === CommissionType::Percentage ? (int) $current->commission_value : self::FALLBACK_DEFAULT_RATE;

        DB::transaction(function () use ($current, $data, $request) {
            if ($current) {
                $current->update(['is_active' => false]);
            }
            CommissionConfig::create([
                'scope' => CommissionScope::Global,
                'club_id' => null,
                'venue_id' => null,
                'commission_type' => CommissionType::Percentage,
                'commission_value' => (int) $data['rate'],
                'cancellation_fee' => $current?->cancellation_fee ?? 0,
                'is_active' => true,
                'effective_from' => $data['effective_from'],
                'note' => $data['reason'],
                'created_by' => $request->user()->id,
            ]);
        });

        $affectedClubs = Club::query()
            ->whereDoesntHave('commissionConfigs', fn (Builder $q) => $q
                ->where('scope', CommissionScope::Club)
                ->where('is_active', true))
            ->count();

        activity('commission')
            ->causedBy($request->user())
            ->withProperties([
                'old_rate' => $oldRate,
                'new_rate' => (int) $data['rate'],
                'reason' => $data['reason'],
                'affected_clubs' => $affectedClubs,
                'effective_from' => $data['effective_from'],
            ])
            ->log('platform_default_updated');

        return back()
            ->with('flash_key', 'commissionsDefaultUpdated')
            ->with('flash_type', 'success');
    }

    public function storeOverride(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'club_id' => ['required', 'integer', 'exists:clubs,id'],
            'rate' => ['required', 'integer', 'min:0', 'max:50'],
            'effective_from' => ['required', 'date'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $club = Club::findOrFail($data['club_id']);

        // Deactivate any existing active override on this club, then create fresh.
        DB::transaction(function () use ($club, $data, $request) {
            CommissionConfig::query()
                ->where('scope', CommissionScope::Club)
                ->where('club_id', $club->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            CommissionConfig::create([
                'scope' => CommissionScope::Club,
                'club_id' => $club->id,
                'venue_id' => null,
                'commission_type' => CommissionType::Percentage,
                'commission_value' => (int) $data['rate'],
                'cancellation_fee' => 0,
                'is_active' => true,
                'effective_from' => $data['effective_from'],
                'note' => $data['reason'],
                'created_by' => $request->user()->id,
            ]);
        });

        activity('commission')
            ->performedOn($club)
            ->causedBy($request->user())
            ->withProperties([
                'club_id' => $club->id,
                'rate' => (int) $data['rate'],
                'reason' => $data['reason'],
                'effective_from' => $data['effective_from'],
            ])
            ->log('club_override_set');

        return back()
            ->with('flash_key', 'commissionsOverrideSet')
            ->with('flash_type', 'success');
    }

    public function removeOverride(Request $request, Club $club): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $config = CommissionConfig::query()
            ->where('scope', CommissionScope::Club)
            ->where('club_id', $club->id)
            ->where('is_active', true)
            ->first();

        if (! $config) {
            return back()->with('flash_key', 'commissionsOverrideMissing')->with('flash_type', 'error');
        }

        $oldRate = (int) $config->commission_value;
        $config->update(['is_active' => false]);

        activity('commission')
            ->performedOn($club)
            ->causedBy($request->user())
            ->withProperties([
                'club_id' => $club->id,
                'old_rate' => $oldRate,
                'reason' => $data['reason'],
            ])
            ->log('club_override_removed');

        return back()
            ->with('flash_key', 'commissionsOverrideRemoved')
            ->with('flash_type', 'success');
    }

    // ---------- helpers ----------

    /** @return array<string, mixed> */
    private function buildClubTable(array $filters, ?int $defaultRate, Carbon $from, Carbon $to, $overridesByClub): array
    {
        $revenueSub = DB::table('bookings')
            ->selectRaw('COALESCE(SUM(bookings.total_price), 0)')
            ->join('venues', 'bookings.venue_id', '=', 'venues.id')
            ->whereColumn('venues.club_id', 'clubs.id')
            ->whereIn('bookings.status', ['confirmed', 'completed'])
            ->whereBetween('bookings.booking_date', [$from->toDateString(), $to->toDateString()]);

        $commissionSub = DB::table('bookings')
            ->selectRaw('COALESCE(SUM(bookings.commission_amount), 0)')
            ->join('venues', 'bookings.venue_id', '=', 'venues.id')
            ->whereColumn('venues.club_id', 'clubs.id')
            ->whereIn('bookings.status', ['confirmed', 'completed'])
            ->whereBetween('bookings.booking_date', [$from->toDateString(), $to->toDateString()]);

        $query = Club::query()
            ->with('city:id,name,name_ar')
            ->addSelect([
                'revenue_30d' => $revenueSub,
                'commission_30d' => $commissionSub,
            ])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(function (Builder $q) use ($s) {
                $q->where('name->ar', 'like', "%{$s}%")->orWhere('name->en', 'like', "%{$s}%");
            }))
            ->when(! empty($filters['city_id']), fn ($q) => $q->where('city_id', $filters['city_id']))
            ->when(($filters['has_override'] ?? '') === 'yes', fn ($q) => $q->whereIn('id', $overridesByClub->keys()))
            ->when(($filters['has_override'] ?? '') === 'no', fn ($q) => $q->whereNotIn('id', $overridesByClub->keys()))
            ->orderBy('name->ar');

        $paginated = $query->paginate(20)->withQueryString()->through(function (Club $club) use ($defaultRate, $overridesByClub) {
            $override = $overridesByClub->get($club->id);
            $overrideRate = $override ? (int) $override->commission_value : null;

            return [
                'id' => $club->id,
                'slug' => $club->slug,
                'name' => $club->getTranslations('name'),
                'city' => $club->city ? ['id' => $club->city->id, 'name' => $club->city->name, 'name_ar' => $club->city->name_ar] : null,
                'default_rate' => $defaultRate,
                'override_rate' => $overrideRate,
                'effective_from' => $override?->effective_from?->toDateString(),
                'note' => $override?->note,
                'has_override' => $override !== null,
                'revenue_30d' => (int) ($club->revenue_30d ?? 0),
                'commission_30d' => (int) ($club->commission_30d ?? 0),
            ];
        });

        // Apply rate-range filter after hydration (effective rate depends on override OR default).
        if ($filters['rate_min'] !== null && $filters['rate_min'] !== '') {
            $min = (int) $filters['rate_min'];
            $paginated->setCollection(
                $paginated->getCollection()->filter(fn ($r) => ($r['override_rate'] ?? $r['default_rate'] ?? 0) >= $min)->values()
            );
        }
        if ($filters['rate_max'] !== null && $filters['rate_max'] !== '') {
            $max = (int) $filters['rate_max'];
            $paginated->setCollection(
                $paginated->getCollection()->filter(fn ($r) => ($r['override_rate'] ?? $r['default_rate'] ?? 0) <= $max)->values()
            );
        }

        return $paginated->toArray();
    }

    /** @return array<string, mixed> */
    private function analytics(Carbon $from, Carbon $to): array
    {
        $row = Booking::query()
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereBetween('booking_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('COALESCE(SUM(commission_amount),0) as total, COALESCE(SUM(total_price),0) as revenue')
            ->first();

        $total = (int) ($row->total ?? 0);
        $revenue = (int) ($row->revenue ?? 0);
        $avgRate = $revenue > 0 ? round(($total / $revenue) * 100, 2) : 0;

        $topEarning = DB::table('bookings')
            ->join('venues', 'bookings.venue_id', '=', 'venues.id')
            ->join('clubs', 'venues.club_id', '=', 'clubs.id')
            ->whereIn('bookings.status', ['confirmed', 'completed'])
            ->whereBetween('bookings.booking_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('clubs.id', 'clubs.name')
            ->selectRaw('clubs.id, clubs.name as club_name,
                COALESCE(SUM(bookings.commission_amount),0) as commission,
                COALESCE(SUM(bookings.total_price),0) as revenue')
            ->orderByDesc('commission')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'club_id' => (int) $r->id,
                'club_name' => json_decode($r->club_name, true) ?: ['ar' => $r->club_name, 'en' => $r->club_name],
                'commission' => (int) $r->commission,
                'revenue' => (int) $r->revenue,
                'rate' => $r->revenue > 0 ? round(($r->commission / $r->revenue) * 100, 2) : 0,
            ])->all();

        $trend = Booking::query()
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereBetween('booking_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('DATE(booking_date) as day, COALESCE(SUM(commission_amount),0) as commission')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($r) => ['day' => (string) $r->day, 'commission' => (int) $r->commission])
            ->all();

        return [
            'total_commission' => $total,
            'avg_rate' => $avgRate,
            'top_earning_clubs' => $topEarning,
            'trend' => $trend,
        ];
    }
}
