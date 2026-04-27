<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Venue;
use App\Repositories\Contracts\CityRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PlayerController extends Controller
{
    public function __construct(
        private CityRepositoryInterface $cityRepo,
    ) {}

    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'city_id' => $request->integer('city_id') ?: null,
            'status' => $request->string('status')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
            'spending_min' => $request->input('spending_min'),
            'spending_max' => $request->input('spending_max'),
            'last_booking_from' => $request->string('last_booking_from')->toString(),
            'last_booking_to' => $request->string('last_booking_to')->toString(),
        ];

        $spentSubquery = Booking::selectRaw('COALESCE(SUM(total_price), 0)')
            ->whereColumn('bookings.user_id', 'users.id')
            ->whereIn('status', ['confirmed', 'completed']);

        $lastBookingSub = Booking::selectRaw('MAX(booking_date)')
            ->whereColumn('bookings.user_id', 'users.id');

        $query = $this->playerQuery()
            ->with(['defaultCity:id,name,name_ar'])
            ->withCount('bookings')
            ->addSelect([
                'total_spent' => $spentSubquery,
                'last_booking_date' => $lastBookingSub,
            ])
            ->when($filters['search'], fn ($q, $s) => $q->where(function (Builder $q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('phone_number', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%");
            }))
            ->when($filters['city_id'], fn ($q, $id) => $q->where('default_city_id', $id))
            ->when($filters['status'], fn ($q, $s) => $q->where('account_status', $s))
            ->when($filters['date_from'], fn ($q, $d) => $q->whereDate('users.created_at', '>=', $d))
            ->when($filters['date_to'], fn ($q, $d) => $q->whereDate('users.created_at', '<=', $d))
            ->when($filters['spending_min'] !== null && $filters['spending_min'] !== '', fn ($q) => $q->whereRaw('('.$spentSubquery->toSql().') >= ?', [...$spentSubquery->getBindings(), (int) $filters['spending_min']]))
            ->when($filters['spending_max'] !== null && $filters['spending_max'] !== '', fn ($q) => $q->whereRaw('('.$spentSubquery->toSql().') <= ?', [...$spentSubquery->getBindings(), (int) $filters['spending_max']]))
            ->when($filters['last_booking_from'], fn ($q, $d) => $q->whereRaw('('.$lastBookingSub->toSql().') >= ?', [...$lastBookingSub->getBindings(), $d]))
            ->when($filters['last_booking_to'], fn ($q, $d) => $q->whereRaw('('.$lastBookingSub->toSql().') <= ?', [...$lastBookingSub->getBindings(), $d]))
            ->orderByDesc('users.id');

        $players = $query->paginate(20)->withQueryString()->through(fn (User $u) => $this->indexRow($u));

        return Inertia::render('Admin/Players/Index', [
            'players' => $players,
            'filters' => $filters,
            'stats' => $this->stats(),
            'options' => [
                'cities' => $this->cityOptions(),
                'statuses' => array_map(fn ($c) => $c->value, AccountStatus::cases()),
            ],
        ]);
    }

    public function show(User $player, Request $request): Response
    {
        $this->ensurePlayer($player);

        $player->load(['defaultCity:id,name,name_ar']);

        $totalBookings = $player->bookings()->count();
        $totalSpent = (int) $player->bookings()
            ->whereIn('status', ['confirmed', 'completed'])
            ->sum('total_price');

        $bookingsThisMonth = $player->bookings()
            ->whereYear('booking_date', now()->year)
            ->whereMonth('booking_date', now()->month)
            ->count();

        $favouriteSport = DB::table('bookings')
            ->join('venues', 'bookings.venue_id', '=', 'venues.id')
            ->join('venue_categories', 'venues.category_id', '=', 'venue_categories.id')
            ->where('bookings.user_id', $player->id)
            ->groupBy('venue_categories.id', 'venue_categories.name')
            ->orderByRaw('COUNT(*) DESC')
            ->select('venue_categories.name')
            ->first();

        $favouriteVenue = DB::table('bookings')
            ->join('venues', 'bookings.venue_id', '=', 'venues.id')
            ->where('bookings.user_id', $player->id)
            ->groupBy('venues.id', 'venues.name')
            ->orderByRaw('COUNT(*) DESC')
            ->select('venues.name', DB::raw('COUNT(*) as bookings_count'))
            ->first();

        $analytics = [
            'total_bookings' => $totalBookings,
            'total_spent' => $totalSpent,
            'avg_booking_value' => $totalBookings > 0 ? (int) round($totalSpent / $totalBookings) : 0,
            'bookings_this_month' => $bookingsThisMonth,
            'favorite_sport' => $this->translatedJson($favouriteSport?->name),
            'favorite_venue' => $this->translatedJson($favouriteVenue?->name),
        ];

        $bookingsFilters = [
            'status' => $request->string('bookings_status')->toString(),
            'date_from' => $request->string('bookings_from')->toString(),
            'date_to' => $request->string('bookings_to')->toString(),
        ];

        $bookings = $player->bookings()
            ->with(['venue:id,club_id,category_id,name', 'venue.club:id,name', 'venue.category:id,name'])
            ->when($bookingsFilters['status'], fn ($q, $s) => $q->where('status', $s))
            ->when($bookingsFilters['date_from'], fn ($q, $d) => $q->whereDate('booking_date', '>=', $d))
            ->when($bookingsFilters['date_to'], fn ($q, $d) => $q->whereDate('booking_date', '<=', $d))
            ->orderByDesc('booking_date')
            ->orderByDesc('start_time')
            ->paginate(20, ['*'], 'bookings_page')
            ->withQueryString()
            ->through(fn (Booking $b) => $this->bookingRow($b));

        $paymentsFilters = [
            'status' => $request->string('payments_status')->toString(),
            'date_from' => $request->string('payments_from')->toString(),
            'date_to' => $request->string('payments_to')->toString(),
        ];

        $payments = $player->payments()
            ->with('booking:id,booking_code')
            ->when($paymentsFilters['status'], fn ($q, $s) => $q->where('status', $s))
            ->when($paymentsFilters['date_from'], fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($paymentsFilters['date_to'], fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest()
            ->paginate(20, ['*'], 'payments_page')
            ->withQueryString()
            ->through(fn (Payment $p) => $this->paymentRow($p));

        $favoriteVenues = $this->favoriteVenues($player);
        $spendingByMonth = $this->spendingByMonth($player);

        return Inertia::render('Admin/Players/Show', [
            'player' => $this->showPayload($player),
            'analytics' => $analytics,
            'spending_by_month' => $spendingByMonth,
            'bookings' => $bookings,
            'payments' => $payments,
            'favorite_venues' => $favoriteVenues,
            'bookings_filters' => $bookingsFilters,
            'payments_filters' => $paymentsFilters,
        ]);
    }

    public function edit(User $player): Response
    {
        $this->ensurePlayer($player);
        $player->load(['defaultCity:id,name,name_ar']);

        return Inertia::render('Admin/Players/Form', [
            'player' => $this->showPayload($player),
            'cities' => $this->cityOptions(),
            'statuses' => array_map(fn ($c) => $c->value, AccountStatus::cases()),
        ]);
    }

    public function update(Request $request, User $player): RedirectResponse
    {
        $this->ensurePlayer($player);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'default_city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'address' => ['nullable', 'string', 'max:500'],
            'account_status' => ['required', 'in:'.implode(',', array_column(AccountStatus::cases(), 'value'))],
            'block_reason' => ['nullable', 'string', 'max:1000', 'required_if:account_status,blocked', 'min:10'],
        ]);

        $wasBlocked = $player->account_status === AccountStatus::Blocked;
        $willBlock = $data['account_status'] === AccountStatus::Blocked->value;

        if (! $willBlock) {
            $data['block_reason'] = null;
            if ($wasBlocked) {
                $data['unblocked_at'] = now();
            }
        } elseif (! $wasBlocked) {
            $data['blocked_at'] = now();
            $data['blocked_by'] = $request->user()->id;
        }

        $player->update($data);

        return redirect()->route('admin.players.show', $player)
            ->with('flash_key', 'playerUpdated')
            ->with('flash_type', 'success');
    }

    public function block(Request $request, User $player): RedirectResponse
    {
        $this->ensurePlayer($player);

        if ($player->account_status === AccountStatus::Blocked) {
            return back()->with('flash_key', 'playerAlreadyBlocked')->with('flash_type', 'error');
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'send_email' => ['nullable', 'boolean'],
        ]);

        $player->update([
            'account_status' => AccountStatus::Blocked,
            'block_reason' => $data['reason'],
            'blocked_at' => now(),
            'blocked_by' => $request->user()->id,
        ]);

        // TODO: if ($data['send_email'] ?? false) dispatch(new SendPlayerBlockedEmail($player, $data['reason']));

        return back()->with('flash_key', 'playerBlocked')->with('flash_type', 'success');
    }

    public function unblock(Request $request, User $player): RedirectResponse
    {
        $this->ensurePlayer($player);

        if ($player->account_status !== AccountStatus::Blocked) {
            return back()->with('flash_key', 'playerNotBlocked')->with('flash_type', 'error');
        }

        $player->update([
            'account_status' => AccountStatus::Active,
            'block_reason' => null,
            'unblocked_at' => now(),
        ]);

        // TODO: if ($request->boolean('send_email')) dispatch(new SendPlayerUnblockedEmail($player));

        return back()->with('flash_key', 'playerUnblocked')->with('flash_type', 'success');
    }

    // ---------- helpers ----------

    private function ensurePlayer(User $user): void
    {
        if (! $user->hasRole(UserRole::Player->value)) {
            abort(404);
        }
    }

    private function playerQuery(): Builder
    {
        // Spatie permission: scope to users with the "player" role via pivot.
        return User::query()->role(UserRole::Player->value);
    }

    /** @return array<string, mixed> */
    private function indexRow(User $u): array
    {
        return [
            'id' => $u->id,
            'name' => $u->name,
            'phone_number' => $u->phone_number,
            'email' => $u->email,
            'account_status' => $u->account_status instanceof AccountStatus ? $u->account_status->value : $u->account_status,
            'created_at' => $u->created_at?->toIso8601String(),
            'last_login_at' => $u->last_login_at?->toIso8601String(),
            'bookings_count' => (int) ($u->bookings_count ?? 0),
            'total_spent' => (int) ($u->total_spent ?? 0),
            'last_booking_date' => $u->last_booking_date,
            'city' => $u->defaultCity ? ['id' => $u->defaultCity->id, 'name' => $u->defaultCity->name, 'name_ar' => $u->defaultCity->name_ar] : null,
        ];
    }

    /** @return array<string, mixed> */
    private function showPayload(User $u): array
    {
        return [
            'id' => $u->id,
            'name' => $u->name,
            'phone_number' => $u->phone_number,
            'email' => $u->email,
            'address' => $u->address,
            'default_city_id' => $u->default_city_id,
            'account_status' => $u->account_status instanceof AccountStatus ? $u->account_status->value : $u->account_status,
            'block_reason' => $u->block_reason,
            'created_at' => $u->created_at?->toIso8601String(),
            'blocked_at' => $u->blocked_at?->toIso8601String(),
            'unblocked_at' => $u->unblocked_at?->toIso8601String(),
            'last_login_at' => $u->last_login_at?->toIso8601String(),
            'phone_verified_at' => $u->phone_verified_at?->toIso8601String(),
            'email_verified_at' => $u->email_verified_at?->toIso8601String(),
            'city' => $u->defaultCity ? ['id' => $u->defaultCity->id, 'name' => $u->defaultCity->name, 'name_ar' => $u->defaultCity->name_ar] : null,
        ];
    }

    /** @return array<string, mixed> */
    private function bookingRow(Booking $b): array
    {
        $venue = $b->venue;

        return [
            'id' => $b->id,
            'booking_code' => $b->booking_code,
            'booking_date' => $b->booking_date instanceof Carbon ? $b->booking_date->toDateString() : (string) $b->booking_date,
            'start_time' => $b->start_time,
            'end_time' => $b->end_time,
            'status' => $b->status,
            'total_price' => (int) $b->total_price,
            'venue' => $venue ? [
                'id' => $venue->id,
                'name' => $venue->getTranslations('name'),
                'club' => $venue->club ? ['id' => $venue->club->id, 'name' => $venue->club->getTranslations('name')] : null,
                'category' => $venue->category ? ['id' => $venue->category->id, 'name' => $venue->category->getTranslations('name')] : null,
            ] : null,
        ];
    }

    /** @return array<string, mixed> */
    private function paymentRow(Payment $p): array
    {
        return [
            'id' => $p->id,
            'created_at' => $p->created_at?->toIso8601String(),
            'booking_code' => $p->booking?->booking_code,
            'booking_id' => $p->booking_id,
            'provider' => $p->provider,
            'amount' => (int) $p->amount,
            'currency' => $p->currency,
            'status' => $p->status,
            'provider_transaction_id' => $p->provider_transaction_id,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function favoriteVenues(User $player): array
    {
        $rows = DB::table('bookings')
            ->join('venues', 'bookings.venue_id', '=', 'venues.id')
            ->leftJoin('clubs', 'venues.club_id', '=', 'clubs.id')
            ->leftJoin('venue_categories', 'venues.category_id', '=', 'venue_categories.id')
            ->where('bookings.user_id', $player->id)
            ->groupBy('venues.id', 'venues.name', 'clubs.name', 'venue_categories.name')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(5)
            ->select(
                'venues.id',
                'venues.slug as venue_slug',
                'venues.name as venue_name',
                'clubs.name as club_name',
                'venue_categories.name as sport_name',
                DB::raw('COUNT(*) as bookings_count'),
            )
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        // Resolve cover photos via Spatie MediaLibrary for each venue id.
        $venueIds = $rows->pluck('id')->all();
        $venues = Venue::whereIn('id', $venueIds)->with('media')->get()->keyBy('id');

        return $rows->map(function ($row) use ($venues) {
            $venue = $venues[$row->id] ?? null;
            $cover = $venue?->getMedia('images')->sortBy('order_column')->first();

            return [
                'id' => (int) $row->id,
                'slug' => (string) $row->venue_slug,
                'name' => $this->translatedJson($row->venue_name),
                'club_name' => $this->translatedJson($row->club_name),
                'sport_name' => $this->translatedJson($row->sport_name),
                'bookings_count' => (int) $row->bookings_count,
                'cover_url' => $cover?->getUrl(),
            ];
        })->values()->all();
    }

    /** @return array<int, array{month:string,month_en:string,spent:int}> */
    private function spendingByMonth(User $player): array
    {
        $start = Carbon::now()->subMonths(5)->startOfMonth();
        $rows = Booking::query()
            ->selectRaw("DATE_FORMAT(booking_date, '%Y-%m') as m, COALESCE(SUM(total_price), 0) as spent")
            ->where('user_id', $player->id)
            ->whereIn('status', ['confirmed', 'completed'])
            ->where('booking_date', '>=', $start->toDateString())
            ->groupBy('m')
            ->pluck('spent', 'm')
            ->all();

        $out = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = Carbon::now()->subMonths($i);
            $key = $m->format('Y-m');
            $out[] = [
                'month' => $m->locale('ar')->translatedFormat('F Y'),
                'month_en' => $m->locale('en')->translatedFormat('M Y'),
                'spent' => (int) ($rows[$key] ?? 0),
            ];
        }

        return $out;
    }

    /** @return array<string, int|float> */
    private function stats(): array
    {
        $base = $this->playerQuery();

        $byStatus = (clone $base)
            ->selectRaw('account_status, COUNT(*) as c')
            ->groupBy('account_status')
            ->pluck('c', 'account_status')
            ->all();

        $total = (int) (clone $base)->count();

        $totalSpending = (int) DB::table('bookings')
            ->join('users', 'bookings.user_id', '=', 'users.id')
            ->whereIn('bookings.status', ['confirmed', 'completed'])
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('model_has_roles')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->whereColumn('model_has_roles.model_id', 'users.id')
                    ->where('roles.name', UserRole::Player->value)
                    ->where('model_has_roles.model_type', User::class);
            })
            ->sum('bookings.total_price');

        $zeroBookings = (int) (clone $base)
            ->whereDoesntHave('bookings')
            ->count();

        return [
            'total' => $total,
            'active' => (int) ($byStatus[AccountStatus::Active->value] ?? 0),
            'blocked' => (int) ($byStatus[AccountStatus::Blocked->value] ?? 0),
            'total_spending' => $totalSpending,
            'avg_spending' => $total > 0 ? (int) round($totalSpending / $total) : 0,
            'zero_bookings' => $zeroBookings,
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

    /**
     * Some venue/category/club name columns are JSON-translated strings.
     * Return the raw translations array so the frontend can pick the right locale.
     *
     * @return array<string, string>
     */
    private function translatedJson(?string $raw): array
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
}
