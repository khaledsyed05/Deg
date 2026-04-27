<?php

namespace App\Http\Controllers\Club;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Club;
use App\Models\Settlement;
use App\Models\SettlementItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SettlementController extends Controller
{
    /** Minimum net payout required before a club can request a settlement. */
    private const MIN_THRESHOLD = 50_000;

    public function index(Request $request): Response|RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $filters = [
            'status' => $request->string('status')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
        ];

        $venueIds = $club->venues()->pluck('id');

        $unsettledQuery = Booking::query()
            ->whereIn('venue_id', $venueIds)
            ->where('status', BookingStatus::Completed->value)
            ->whereNotIn('id', DB::table('settlement_items')->select('booking_id'));

        $pendingAmount = (int) (clone $unsettledQuery)->sum('club_payout_amount');
        $pendingBookingsCount = (int) (clone $unsettledQuery)->count();

        $lastSettlement = Settlement::where('club_id', $club->id)
            ->latest('created_at')
            ->first();

        $nextSettlementDate = ($lastSettlement?->created_at ?? now())->copy()->addWeek();

        $query = Settlement::query()
            ->where('club_id', $club->id)
            ->withCount('items as bookings_count');

        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }
        if ($filters['date_from']) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if ($filters['date_to']) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $settlements = $query
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Settlement $s) => [
                'id' => $s->id,
                'settlement_number' => $this->settlementNumber($s->id),
                'request_date' => $s->created_at?->toDateString(),
                'period_from' => $s->period_from?->toDateString(),
                'period_to' => $s->period_to?->toDateString(),
                'total_amount' => (int) $s->net_payable,
                'bookings_count' => (int) ($s->bookings_count ?? 0),
                'status' => $s->status,
            ]);

        return Inertia::render('Club/Finances/Settlements/Index', [
            'summary' => [
                'pending_amount' => $pendingAmount,
                'pending_bookings_count' => $pendingBookingsCount,
                'next_settlement_date' => $nextSettlementDate->toDateString(),
                'last_settlement_date' => $lastSettlement?->created_at?->toDateString(),
                'total_settlements' => Settlement::where('club_id', $club->id)->count(),
            ],
            'settlements' => $settlements,
            'filters' => $filters,
            'canRequestSettlement' => $pendingAmount >= self::MIN_THRESHOLD,
            'minThreshold' => self::MIN_THRESHOLD,
        ]);
    }

    public function show(Settlement $settlement): Response|RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        if ((int) $settlement->club_id !== (int) $club->id) {
            abort(403, 'غير مخوّل للوصول إلى هذه التسوية.');
        }

        $locale = app()->getLocale();

        $settlement->load([
            'items.booking:id,booking_code,booking_date,venue_id,user_id',
            'items.booking.venue:id,slug,name',
            'items.booking.user:id,name',
        ]);

        $bookings = $settlement->items->map(function (SettlementItem $item) use ($locale) {
            $b = $item->booking;
            $rate = $item->venue_price > 0
                ? round(((int) $item->commission_amount / (int) $item->venue_price) * 100, 2)
                : 0.0;

            return [
                'id' => $b?->id,
                'booking_code' => $b?->booking_code ?? '—',
                'date' => $b?->booking_date?->toDateString(),
                'venue' => [
                    'slug' => $b?->venue?->slug,
                    'name' => $b?->venue?->getTranslation('name', $locale)
                        ?: $b?->venue?->getTranslation('name', 'ar')
                        ?: '—',
                ],
                'player' => $b?->user?->name ?? '—',
                'total_amount' => (int) $item->venue_price,
                'commission_amount' => (int) $item->commission_amount,
                'commission_rate' => $rate,
                'net_amount' => (int) $item->club_payout_amount,
            ];
        })->values();

        $totals = [
            'gross_revenue' => (int) $settlement->total_venue_price,
            'commission' => (int) $settlement->total_commission,
            'net_payout' => (int) $settlement->net_payable,
        ];

        return Inertia::render('Club/Finances/Settlements/Show', [
            'settlement' => [
                'id' => $settlement->id,
                'settlement_number' => $this->settlementNumber($settlement->id),
                'status' => $settlement->status,
                'request_date' => $settlement->created_at?->toIso8601String(),
                'period_from' => $settlement->period_from?->toDateString(),
                'period_to' => $settlement->period_to?->toDateString(),
                'bookings_count' => $settlement->items->count(),
                'total_amount' => (int) $settlement->net_payable,
                'paid_amount' => (int) $settlement->paid_amount,
                'settled_at' => $settlement->settled_at?->toIso8601String(),
                'payment_method' => $settlement->payment_method,
                'payment_reference' => $settlement->payment_reference,
                'note' => $settlement->note,
            ],
            'bookings' => $bookings,
            'totals' => $totals,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $venueIds = $club->venues()->pluck('id');

        $bookings = Booking::query()
            ->whereIn('venue_id', $venueIds)
            ->where('status', BookingStatus::Completed->value)
            ->whereNotIn('id', DB::table('settlement_items')->select('booking_id'))
            ->get([
                'id', 'booking_date', 'venue_price', 'commission_amount',
                'club_payout_amount', 'cancellation_commission', 'total_price',
            ]);

        if ($bookings->isEmpty()) {
            return back()
                ->with('flash_key', 'noBookingsToSettle')
                ->with('flash_type', 'error');
        }

        $netPayable = (int) $bookings->sum('club_payout_amount');
        if ($netPayable < self::MIN_THRESHOLD) {
            return back()
                ->with('flash_key', 'settlementBelowMinimum')
                ->with('flash_type', 'error');
        }

        $settlement = DB::transaction(function () use ($club, $bookings, $netPayable) {
            $settlement = Settlement::create([
                'club_id' => $club->id,
                'period_from' => $bookings->min('booking_date'),
                'period_to' => $bookings->max('booking_date'),
                'total_bookings' => $bookings->count(),
                'total_venue_price' => (int) $bookings->sum('venue_price'),
                'total_commission' => (int) $bookings->sum('commission_amount'),
                'total_cancellation_fees' => (int) $bookings->sum('cancellation_commission'),
                'net_payable' => $netPayable,
                'paid_amount' => 0,
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            $rows = $bookings->map(fn ($b) => [
                'settlement_id' => $settlement->id,
                'booking_id' => $b->id,
                'venue_price' => (int) $b->venue_price,
                'commission_amount' => (int) $b->commission_amount,
                'club_payout_amount' => (int) $b->club_payout_amount,
                'cancellation_comm' => (int) $b->cancellation_commission,
                'created_at' => now(),
            ])->all();

            SettlementItem::insert($rows);

            return $settlement;
        });

        activity()
            ->performedOn($settlement)
            ->causedBy(Auth::user())
            ->withProperties([
                'bookings_count' => $bookings->count(),
                'net_payable' => $netPayable,
            ])
            ->log('club_settlement_requested');

        return redirect()
            ->route('club.finances.settlements.show', $settlement->id)
            ->with('flash_key', 'settlementRequested')
            ->with('flash_type', 'success');
    }

    private function settlementNumber(int $id): string
    {
        return 'STL-'.str_pad((string) $id, 8, '0', STR_PAD_LEFT);
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
