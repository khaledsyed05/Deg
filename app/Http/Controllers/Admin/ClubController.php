<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClubStatus;
use App\Enums\VenueStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Club;
use App\Repositories\Contracts\CityRepositoryInterface;
use App\Repositories\Contracts\ClubRepositoryInterface;
use App\Services\Club\ClubApprovalService;
use App\Services\Notification\WhatsAppService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ClubController extends Controller
{
    public function __construct(
        private ClubRepositoryInterface $clubRepo,
        private CityRepositoryInterface $cityRepo,
        private ClubApprovalService $approvalService,
        private WhatsAppService $whatsapp,
    ) {}

    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'city_id' => $request->integer('city_id') ?: null,
            'revenue_min' => $request->input('revenue_min'),
            'revenue_max' => $request->input('revenue_max'),
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
        ];

        $revenueSubquery = Booking::selectRaw('COALESCE(SUM(bookings.total_price), 0)')
            ->join('venues', 'venues.id', '=', 'bookings.venue_id')
            ->whereColumn('venues.club_id', 'clubs.id');

        $clubs = $this->clubRepo->query()
            ->with(['city:id,name,name_ar', 'owner:id,name,phone_number,email'])
            ->withCount('venues')
            ->addSelect(['total_revenue' => $revenueSubquery])
            ->when($filters['search'], fn ($q, $s) => $q->where(function (Builder $q) use ($s) {
                $q->where('name->ar', 'like', "%{$s}%")
                    ->orWhere('name->en', 'like', "%{$s}%")
                    ->orWhere('phone_number', 'like', "%{$s}%")
                    ->orWhereHas('owner', fn (Builder $q) => $q
                        ->where('name', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%")
                        ->orWhere('phone_number', 'like', "%{$s}%"));
            }))
            ->when($filters['status'], fn ($q, $s) => $q->where('status', $s))
            ->when($filters['city_id'], fn ($q, $id) => $q->where('city_id', $id))
            ->when($filters['date_from'], fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['date_to'], fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($filters['revenue_min'] !== null && $filters['revenue_min'] !== '', fn ($q) => $q->whereRaw('('.$revenueSubquery->toSql().') >= ?', [...$revenueSubquery->getBindings(), (int) $filters['revenue_min']]))
            ->when($filters['revenue_max'] !== null && $filters['revenue_max'] !== '', fn ($q) => $q->whereRaw('('.$revenueSubquery->toSql().') <= ?', [...$revenueSubquery->getBindings(), (int) $filters['revenue_max']]))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Club $c) => $this->indexRow($c));

        return Inertia::render('Admin/Clubs/Index', [
            'clubs' => $clubs,
            'filters' => $filters,
            'stats' => $this->stats(),
            'options' => [
                'cities' => $this->cityOptions(),
                'statuses' => array_map(fn ($c) => $c->value, ClubStatus::cases()),
            ],
        ]);
    }

    public function show(Club $club): Response
    {
        $club->load([
            'city:id,name,name_ar',
            'owner:id,name,phone_number,email',
            'approvedBy:id,name',
            'venues:id,club_id,category_id,name,status,avg_rating,price_from',
            'venues.category:id,name',
        ]);

        $venueIds = $club->venues->pluck('id');
        $totalBookings = Booking::whereIn('venue_id', $venueIds)->count();
        $totalRevenue = (int) Booking::whereIn('venue_id', $venueIds)->sum('total_price');
        $avgRating = (float) ($club->venues->avg('avg_rating') ?? 0);

        $analytics = [
            'total_venues' => $club->venues->count(),
            'total_bookings' => $totalBookings,
            'total_revenue' => $totalRevenue,
            'average_rating' => round($avgRating, 2),
        ];

        $revenueByMonth = $this->revenueByMonth($venueIds);
        $venueRows = $this->venueTabRows($club);
        $settlements = $club->settlements()
            ->orderByDesc('period_from')
            ->limit(10)
            ->get(['id', 'club_id', 'period_from', 'period_to', 'net_payable', 'paid_amount', 'status', 'settled_at']);

        $whatsappStatus = $this->whatsapp->getSessionStatus($club->id);

        return Inertia::render('Admin/Clubs/Show', [
            'club' => $this->showPayload($club),
            'analytics' => $analytics,
            'revenue_by_month' => $revenueByMonth,
            'venues' => $venueRows,
            'settlements' => $settlements,
            'whatsapp_status' => $whatsappStatus,
        ]);
    }

    public function edit(Club $club): Response
    {
        $club->load(['owner:id,name,phone_number,email', 'city:id,name,name_ar']);

        return Inertia::render('Admin/Clubs/Form', [
            'club' => $this->showPayload($club),
            'cities' => $this->cityOptions(),
            'statuses' => array_map(fn ($c) => $c->value, ClubStatus::cases()),
            'platform_commission_rate' => (float) config('commission.default_rate', 10),
        ]);
    }

    public function update(Request $request, Club $club): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'array'],
            'name.ar' => ['required', 'string', 'max:255'],
            'name.en' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        $club->update($data);

        return redirect()->route('admin.clubs.show', $club)
            ->with('flash_key', 'clubUpdated')
            ->with('flash_type', 'success');
    }

    public function approve(Request $request, Club $club): RedirectResponse
    {
        if ($club->status !== ClubStatus::PendingApproval) {
            return back()->with('flash_key', 'clubNotPending')->with('flash_type', 'error');
        }

        $this->approvalService->approve($club, $request->user()->id);

        // TODO: if ($request->boolean('send_email')) dispatch(new SendClubApprovalEmail($club));

        return back()->with('flash_key', 'clubApproved')->with('flash_type', 'success');
    }

    public function reject(Request $request, Club $club): RedirectResponse
    {
        if ($club->status !== ClubStatus::PendingApproval) {
            return back()->with('flash_key', 'clubNotPending')->with('flash_type', 'error');
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'send_email' => ['nullable', 'boolean'],
        ]);

        $this->approvalService->reject($club, $data['reason'], $request->user()->id);

        // TODO: if ($data['send_email'] ?? false) dispatch(new SendClubRejectionEmail($club, $data['reason']));

        return back()->with('flash_key', 'clubRejected')->with('flash_type', 'success');
    }

    public function suspend(Request $request, Club $club): RedirectResponse
    {
        if ($club->status !== ClubStatus::Active) {
            return back()->with('flash_key', 'clubNotActive')->with('flash_type', 'error');
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'send_email' => ['nullable', 'boolean'],
        ]);

        $this->approvalService->suspend($club, $data['reason'], $request->user()->id);

        // TODO: if ($data['send_email'] ?? false) dispatch(new SendClubSuspensionEmail(...));

        return back()->with('flash_key', 'clubSuspended')->with('flash_type', 'success');
    }

    public function unsuspend(Request $request, Club $club): RedirectResponse
    {
        if ($club->status !== ClubStatus::Suspended) {
            return back()->with('flash_key', 'clubNotSuspended')->with('flash_type', 'error');
        }

        $this->approvalService->reactivate($club);

        // TODO: if ($request->boolean('send_email')) dispatch(new SendClubUnsuspensionEmail($club));

        return back()->with('flash_key', 'clubUnsuspended')->with('flash_type', 'success');
    }

    // ---------- helpers ----------

    /** @return array<string, mixed> */
    private function indexRow(Club $c): array
    {
        return [
            'id' => $c->id,
            'slug' => $c->slug,
            'name' => $c->getTranslations('name'),
            'phone_number' => $c->phone_number,
            'status' => $c->status instanceof ClubStatus ? $c->status->value : $c->status,
            'is_featured' => (bool) $c->is_featured,
            'created_at' => $c->created_at?->toIso8601String(),
            'approved_at' => $c->approved_at?->toIso8601String(),
            'venues_count' => $c->venues_count ?? 0,
            'total_revenue' => (int) ($c->total_revenue ?? 0),
            'city' => $c->city ? ['id' => $c->city->id, 'name' => $c->city->name, 'name_ar' => $c->city->name_ar] : null,
            'owner' => $c->owner ? [
                'id' => $c->owner->id,
                'name' => $c->owner->name,
                'phone_number' => $c->owner->phone_number,
                'email' => $c->owner->email,
            ] : null,
        ];
    }

    /** @return array<string, mixed> */
    private function showPayload(Club $c): array
    {
        return [
            'id' => $c->id,
            'name' => $c->getTranslations('name'),
            'description' => $c->getTranslations('description'),
            'slug' => $c->slug,
            'phone_number' => $c->phone_number,
            'address' => $c->address,
            'status' => $c->status instanceof ClubStatus ? $c->status->value : $c->status,
            'is_featured' => (bool) $c->is_featured,
            'commission_rate' => $c->commission_rate !== null ? (float) $c->commission_rate : null,
            'avg_rating' => $c->avg_rating !== null ? (float) $c->avg_rating : null,
            'reviews_count' => (int) ($c->reviews_count ?? 0),
            'city_id' => $c->city_id,
            'rejection_reason' => $c->rejection_reason,
            'suspension_reason' => $c->suspension_reason,
            'created_at' => $c->created_at?->toIso8601String(),
            'approved_at' => $c->approved_at?->toIso8601String(),
            'rejected_at' => $c->rejected_at?->toIso8601String(),
            'suspended_at' => $c->suspended_at?->toIso8601String(),
            'unsuspended_at' => $c->unsuspended_at?->toIso8601String(),
            'city' => $c->city ? ['id' => $c->city->id, 'name' => $c->city->name, 'name_ar' => $c->city->name_ar] : null,
            'owner' => $c->owner ? [
                'id' => $c->owner->id,
                'name' => $c->owner->name,
                'phone_number' => $c->owner->phone_number,
                'email' => $c->owner->email,
            ] : null,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function venueTabRows(Club $club): array
    {
        $venueIds = $club->venues->pluck('id');

        $bookingAgg = Booking::query()
            ->selectRaw('venue_id, COUNT(*) as bookings_count, COALESCE(SUM(total_price), 0) as revenue')
            ->whereIn('venue_id', $venueIds)
            ->groupBy('venue_id')
            ->get()
            ->keyBy('venue_id');

        return $club->venues->map(fn ($v) => [
            'id' => $v->id,
            'slug' => $v->slug,
            'name' => $v->getTranslations('name'),
            'status' => $v->status instanceof VenueStatus ? $v->status->value : $v->status,
            'avg_rating' => $v->avg_rating !== null ? (float) $v->avg_rating : null,
            'price_from' => $v->price_from,
            'category' => $v->category ? ['id' => $v->category->id, 'name' => $v->category->getTranslations('name')] : null,
            'bookings_count' => (int) ($bookingAgg[$v->id]->bookings_count ?? 0),
            'revenue' => (int) ($bookingAgg[$v->id]->revenue ?? 0),
        ])->values()->all();
    }

    /**
     * @param  Collection<int,int>  $venueIds
     * @return array<int, array{month:string,month_en:string,revenue:int}>
     */
    private function revenueByMonth($venueIds): array
    {
        $start = Carbon::now()->subMonths(5)->startOfMonth();
        $rows = $venueIds->isEmpty() ? [] : Booking::query()
            ->selectRaw("DATE_FORMAT(starts_at, '%Y-%m') as m, COALESCE(SUM(total_price), 0) as revenue")
            ->whereIn('venue_id', $venueIds)
            ->where('starts_at', '>=', $start)
            ->groupBy('m')
            ->pluck('revenue', 'm')
            ->all();

        $out = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = Carbon::now()->subMonths($i);
            $key = $m->format('Y-m');
            $out[] = [
                'month' => $m->locale('ar')->translatedFormat('F Y'),
                'month_en' => $m->locale('en')->translatedFormat('M Y'),
                'revenue' => (int) ($rows[$key] ?? 0),
            ];
        }

        return $out;
    }

    /** @return array<string, int> */
    private function stats(): array
    {
        $counts = $this->clubRepo->query()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->all();

        $totalRevenue = (int) Booking::sum('total_price');

        return [
            'total' => array_sum($counts),
            'pending' => (int) ($counts[ClubStatus::PendingApproval->value] ?? 0),
            'active' => (int) ($counts[ClubStatus::Active->value] ?? 0),
            'rejected' => (int) ($counts[ClubStatus::Rejected->value] ?? 0),
            'suspended' => (int) ($counts[ClubStatus::Suspended->value] ?? 0),
            'total_revenue' => $totalRevenue,
        ];
    }

    /** @return array<int, array{id:int,name:string,name_ar:?string}> */
    private function cityOptions(): array
    {
        return $this->cityRepo->query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'name_ar'])
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'name_ar' => $c->name_ar])
            ->all();
    }
}
