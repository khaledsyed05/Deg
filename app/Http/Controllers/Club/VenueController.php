<?php

namespace App\Http\Controllers\Club;

use App\Enums\BookingStatus;
use App\Enums\DayOfWeek;
use App\Enums\DayType;
use App\Enums\VenueStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Club;
use App\Models\Venue;
use App\Models\VenueCategory;
use App\Repositories\Contracts\SportCategoryRepositoryInterface;
use App\Repositories\Contracts\VenueCategoryRepositoryInterface;
use App\Services\Venue\VenueService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class VenueController extends Controller
{
    /** @var array<int, string> */
    private const DEFAULT_AMENITIES = [
        'parking', 'wifi', 'locker_rooms', 'showers', 'cafeteria', 'first_aid', 'lights', 'ac',
    ];

    public function __construct(
        private VenueService $service,
        private VenueCategoryRepositoryInterface $categories,
        private SportCategoryRepositoryInterface $sports,
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $filters = [
            'search' => $request->string('search')->toString(),
            'sport_type' => $request->integer('sport_type') ?: null,
            'status' => $request->string('status')->toString(),
            'sort' => $request->string('sort')->toString() ?: 'name',
        ];

        $locale = app()->getLocale();
        $today = today();
        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $activeStatuses = [BookingStatus::Confirmed->value, BookingStatus::Completed->value];

        $venueIds = $club->venues()->pluck('id');
        $totalVenues = $venueIds->count();
        $activeVenues = $club->venues()->where('status', VenueStatus::Active->value)->count();

        $todayBookings = Booking::whereIn('venue_id', $venueIds)
            ->whereDate('booking_date', $today)
            ->whereIn('status', $activeStatuses)
            ->count();

        $monthRevenue = (int) Booking::whereIn('venue_id', $venueIds)
            ->whereBetween('booking_date', [$monthStart, $monthEnd])
            ->whereIn('status', $activeStatuses)
            ->sum('total_price');

        $todayBookingsSub = Booking::selectRaw('COUNT(*)')
            ->whereColumn('venue_id', 'venues.id')
            ->whereDate('booking_date', $today)
            ->whereIn('status', $activeStatuses);

        $weekRevenueSub = Booking::selectRaw('COALESCE(SUM(total_price), 0)')
            ->whereColumn('venue_id', 'venues.id')
            ->whereBetween('booking_date', [$weekStart, $weekEnd])
            ->whereIn('status', $activeStatuses);

        $weekBookingsCountSub = Booking::selectRaw('COUNT(*)')
            ->whereColumn('venue_id', 'venues.id')
            ->whereBetween('booking_date', [$weekStart, $weekEnd])
            ->whereIn('status', $activeStatuses);

        $venues = Venue::query()
            ->where('club_id', $club->id)
            ->with(['category:id,name'])
            ->when($filters['search'], fn (Builder $q, $s) => $q->where(function (Builder $q) use ($s) {
                $q->where('name->ar', 'like', "%{$s}%")
                    ->orWhere('name->en', 'like', "%{$s}%");
            }))
            ->when($filters['sport_type'], fn (Builder $q, $id) => $q->where('category_id', $id))
            ->when($filters['status'], fn (Builder $q, $s) => $q->where('status', $s))
            ->addSelect('venues.*')
            ->addSelect(['today_bookings_count' => $todayBookingsSub])
            ->addSelect(['week_revenue_sum' => $weekRevenueSub])
            ->addSelect(['week_bookings_count' => $weekBookingsCountSub])
            ->when($filters['sort'], function (Builder $q, $sort) {
                match ($sort) {
                    'bookings' => $q->orderByDesc('today_bookings_count'),
                    'revenue' => $q->orderByDesc('week_revenue_sum'),
                    'created' => $q->latest(),
                    default => $q->orderBy('name->ar'),
                };
            })
            ->get()
            ->map(function (Venue $v) use ($locale) {
                $weekSlotCapacity = 7 * 12;
                $occupancy = $weekSlotCapacity > 0
                    ? min(100, round(((int) $v->week_bookings_count / $weekSlotCapacity) * 100, 1))
                    : 0;

                return [
                    'id' => $v->id,
                    'slug' => $v->slug,
                    'name' => $v->getTranslation('name', $locale) ?: $v->getTranslation('name', 'ar'),
                    'sport_type' => $v->category?->getTranslation('name', $locale) ?: '—',
                    'capacity' => $v->capacity,
                    'status' => $v->status?->value,
                    'cover_photo' => $v->getFirstMediaUrl('images') ?: null,
                    'today_bookings' => (int) $v->today_bookings_count,
                    'week_revenue' => (int) $v->week_revenue_sum,
                    'occupancy_rate' => (float) $occupancy,
                ];
            });

        return Inertia::render('Club/Venues/Index', [
            'stats' => [
                'total_venues' => $totalVenues,
                'active_venues' => $activeVenues,
                'today_bookings' => $todayBookings,
                'month_revenue' => $monthRevenue,
            ],
            'venues' => $venues,
            'filters' => $filters,
            'sportTypes' => VenueCategory::query()
                ->orderBy('order_column')
                ->get()
                ->map(fn (VenueCategory $c) => [
                    'id' => $c->id,
                    'name' => $c->getTranslation('name', $locale) ?: $c->getTranslation('name', 'ar'),
                ]),
        ]);
    }

    public function create(): Response|RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        return Inertia::render('Club/Venues/Form', [
            'venue' => null,
            'options' => $this->options(),
            'amenities' => self::DEFAULT_AMENITIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $data = $this->validateVenue($request);
        $data['club_id'] = $club->id;

        $venue = $this->service->createVenue($data);

        activity()
            ->performedOn($venue)
            ->causedBy(Auth::user())
            ->log('club_venue_created');

        return redirect()
            ->route('club.venues.index')
            ->with('flash_key', 'venueCreated')
            ->with('flash_type', 'success');
    }

    public function edit(Venue $venue): Response|RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $this->ensureOwnership($venue, $club);

        $venue->load(['sportCategories:id', 'pricingTiers']);

        return Inertia::render('Club/Venues/Form', [
            'venue' => $this->venuePayload($venue),
            'options' => $this->options(),
            'amenities' => self::DEFAULT_AMENITIES,
        ]);
    }

    public function update(Request $request, Venue $venue): RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $this->ensureOwnership($venue, $club);

        $data = $this->validateVenue($request);
        $data['club_id'] = $club->id;

        $this->service->updateVenue($venue, $data);

        activity()
            ->performedOn($venue)
            ->causedBy(Auth::user())
            ->log('club_venue_updated');

        return redirect()
            ->route('club.venues.index')
            ->with('flash_key', 'venueUpdated')
            ->with('flash_type', 'success');
    }

    public function show(Venue $venue): Response|RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $this->ensureOwnership($venue, $club);

        $venue->load(['category', 'pricingTiers', 'sportCategories']);

        $locale = app()->getLocale();
        $activeStatuses = [BookingStatus::Confirmed->value, BookingStatus::Completed->value];
        $last30Start = now()->subDays(30)->startOfDay();

        $totalBookings = Booking::where('venue_id', $venue->id)
            ->whereIn('status', $activeStatuses)
            ->count();

        $totalRevenue = (int) Booking::where('venue_id', $venue->id)
            ->whereIn('status', $activeStatuses)
            ->sum('total_price');

        // Reviews are per-club+booking; scope to this venue via bookings.
        $reviewsQuery = \DB::table('reviews')
            ->join('bookings', 'bookings.id', '=', 'reviews.booking_id')
            ->where('bookings.venue_id', $venue->id)
            ->where('reviews.is_published', 1);

        $avgRating = (float) ((clone $reviewsQuery)->avg('reviews.rating') ?? 0);
        $reviewsCount = (int) (clone $reviewsQuery)->count();

        $totalBookedMinutes = (int) Booking::where('venue_id', $venue->id)
            ->whereBetween('booking_date', [$last30Start, now()])
            ->whereIn('status', $activeStatuses)
            ->sum('duration_minutes');

        $totalAvailableMinutes = 30 * 12 * 60;
        $occupancyRate = $totalAvailableMinutes > 0
            ? min(100, round(($totalBookedMinutes / $totalAvailableMinutes) * 100, 1))
            : 0.0;

        $recentBookings = Booking::where('venue_id', $venue->id)
            ->with(['user:id,name,phone_number'])
            ->orderByDesc('booking_date')
            ->orderByDesc('start_time')
            ->limit(20)
            ->get()
            ->map(fn (Booking $b) => [
                'id' => $b->id,
                'booking_code' => $b->booking_code,
                'date' => $b->booking_date?->toDateString(),
                'time_slot' => substr((string) $b->start_time, 0, 5).' – '.substr((string) $b->end_time, 0, 5),
                'player_name' => $b->user?->name ?? '—',
                'player_phone' => $b->user?->phone_number,
                'status' => $b->status?->value,
                'amount' => (int) $b->total_price,
            ]);

        $revenueChart = Booking::where('venue_id', $venue->id)
            ->whereBetween('booking_date', [$last30Start, now()])
            ->whereIn('status', $activeStatuses)
            ->selectRaw('DATE(booking_date) as date, COALESCE(SUM(total_price), 0) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($r) => ['date' => $r->date, 'revenue' => (int) $r->revenue]);

        $bookingsTrend = Booking::where('venue_id', $venue->id)
            ->whereBetween('booking_date', [$last30Start, now()])
            ->whereIn('status', $activeStatuses)
            ->selectRaw('DATE(booking_date) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($r) => ['date' => $r->date, 'count' => (int) $r->count]);

        $peakHoursRaw = Booking::where('venue_id', $venue->id)
            ->whereBetween('booking_date', [$last30Start, now()])
            ->whereIn('status', $activeStatuses)
            ->selectRaw('HOUR(start_time) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->get()
            ->keyBy('hour');

        $peakHours = collect(range(8, 23))->map(fn ($h) => [
            'hour' => $h,
            'count' => (int) ($peakHoursRaw->get($h)?->count ?? 0),
        ])->values();

        $avgDurationMin = (float) (Booking::where('venue_id', $venue->id)
            ->whereIn('status', $activeStatuses)
            ->avg('duration_minutes') ?? 0);

        $avgValue = $totalBookings > 0 ? (int) round($totalRevenue / $totalBookings) : 0;

        $cancelledCount = Booking::where('venue_id', $venue->id)
            ->where('status', BookingStatus::Cancelled->value)
            ->count();

        $denominator = $totalBookings + $cancelledCount;
        $cancellationRate = $denominator > 0
            ? round(($cancelledCount / $denominator) * 100, 1)
            : 0.0;

        $topPlayers = \DB::table('bookings')
            ->join('users', 'bookings.user_id', '=', 'users.id')
            ->where('bookings.venue_id', $venue->id)
            ->whereIn('bookings.status', $activeStatuses)
            ->select([
                'users.id',
                'users.name',
                \DB::raw('COUNT(bookings.id) as bookings_count'),
                \DB::raw('COALESCE(SUM(bookings.total_price), 0) as total_spent'),
            ])
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('bookings_count')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'name' => $r->name,
                'bookings_count' => (int) $r->bookings_count,
                'total_spent' => (int) $r->total_spent,
            ]);

        return Inertia::render('Club/Venues/Show', [
            'venue' => [
                'id' => $venue->id,
                'slug' => $venue->slug,
                'name' => $venue->getTranslation('name', $locale) ?: $venue->getTranslation('name', 'ar'),
                'description' => $venue->getTranslation('description', $locale) ?: $venue->getTranslation('description', 'ar'),
                'category' => $venue->category?->getTranslation('name', $locale) ?: '—',
                'capacity' => $venue->capacity,
                'size' => $venue->size,
                'price_from' => (int) ($venue->price_from ?? 0),
                'status' => $venue->status instanceof VenueStatus ? $venue->status->value : $venue->status,
                'latitude' => $venue->latitude,
                'longitude' => $venue->longitude,
                'opening_hours' => $this->normalizeOpeningHours($venue->opening_hours ?? []),
                'amenities' => $venue->amenities ?? [],
                'sports' => $venue->sportCategories->map(fn ($s) => $s->getTranslation('name', $locale))->values(),
                'photos' => $venue->getMedia('images')->sortBy('order_column')->map(fn ($m) => [
                    'id' => $m->id,
                    'url' => $m->getUrl(),
                ])->values(),
                'pricing_tiers' => $venue->pricingTiers->sortBy('order_column')->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->getTranslation('name', $locale) ?: '—',
                    'day_type' => $t->day_type instanceof \BackedEnum ? $t->day_type->value : $t->day_type,
                    'specific_day' => $t->specific_day instanceof \BackedEnum ? $t->specific_day->value : $t->specific_day,
                    'start_time' => substr((string) $t->start_time, 0, 5),
                    'end_time' => substr((string) $t->end_time, 0, 5),
                    'duration_minutes' => (int) $t->duration_minutes,
                    'price' => (int) $t->price,
                ])->values(),
            ],
            'stats' => [
                'total_bookings' => $totalBookings,
                'total_revenue' => $totalRevenue,
                'avg_rating' => round($avgRating, 1),
                'reviews_count' => $reviewsCount,
                'occupancy_rate' => $occupancyRate,
            ],
            'recentBookings' => $recentBookings,
            'charts' => [
                'revenue' => $revenueChart,
                'bookings_trend' => $bookingsTrend,
                'peak_hours' => $peakHours,
            ],
            'metrics' => [
                'avg_duration_hours' => round($avgDurationMin / 60, 1),
                'avg_value' => $avgValue,
                'cancellation_rate' => $cancellationRate,
            ],
            'topPlayers' => $topPlayers,
        ]);
    }

    public function destroy(Venue $venue): RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $this->ensureOwnership($venue, $club);

        $blockingStatuses = [
            BookingStatus::Confirmed->value,
            BookingStatus::Scheduled->value,
        ];

        $futureBookings = Booking::where('venue_id', $venue->id)
            ->where('booking_date', '>=', today())
            ->whereIn('status', $blockingStatuses)
            ->count();

        if ($futureBookings > 0) {
            return back()
                ->with('flash_key', 'venueHasFutureBookings')
                ->with('flash_type', 'error');
        }

        activity()
            ->performedOn($venue)
            ->causedBy(Auth::user())
            ->log('club_venue_deleted');

        $venue->delete();

        return redirect()
            ->route('club.venues.index')
            ->with('flash_key', 'venueDeleted')
            ->with('flash_type', 'success');
    }

    // ---------- helpers ----------

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

    private function ensureOwnership(Venue $venue, Club $club): void
    {
        if ((int) $venue->club_id !== (int) $club->id) {
            abort(403, 'غير مخوّل للوصول إلى هذا الملعب.');
        }
    }

    /** @return array<string, mixed> */
    private function validateVenue(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'array'],
            'name.ar' => ['required', 'string', 'max:255'],
            'name.en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'description.ar' => ['nullable', 'string', 'max:5000'],
            'description.en' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['nullable', 'integer', 'exists:venue_categories,id'],
            'size' => ['nullable', 'string', 'max:50'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'price_from' => ['required', 'integer', 'min:0'],
            'sport_ids' => ['nullable', 'array'],
            'sport_ids.*' => ['integer', 'exists:sport_categories,id'],
            'pricing_tiers' => ['nullable', 'array'],
            'pricing_tiers.*.name.ar' => ['required_with:pricing_tiers', 'string', 'max:100'],
            'pricing_tiers.*.name.en' => ['nullable', 'string', 'max:100'],
            'pricing_tiers.*.day_type' => ['required_with:pricing_tiers', 'in:all_days,weekday,weekend,friday,specific_day'],
            'pricing_tiers.*.specific_day' => ['nullable', 'in:saturday,sunday,monday,tuesday,wednesday,thursday,friday'],
            'pricing_tiers.*.start_time' => ['required_with:pricing_tiers', 'date_format:H:i'],
            'pricing_tiers.*.end_time' => ['required_with:pricing_tiers', 'date_format:H:i'],
            'pricing_tiers.*.duration_minutes' => ['required_with:pricing_tiers', 'integer', 'min:15'],
            'pricing_tiers.*.price' => ['required_with:pricing_tiers', 'integer', 'min:0'],
            'pricing_tiers.*.is_active' => ['nullable', 'boolean'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'status' => ['required', 'in:'.implode(',', array_column(VenueStatus::cases(), 'value'))],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['string'],
            'opening_hours' => ['nullable', 'array'],
            'opening_hours.*.day' => ['required_with:opening_hours', 'string'],
            'opening_hours.*.open' => ['required_with:opening_hours', 'string'],
            'opening_hours.*.close' => ['required_with:opening_hours', 'string'],
            'opening_hours.*.closed' => ['nullable', 'boolean'],
            'photos' => ['nullable', 'array', 'max:10'],
            'photos.*' => ['image', 'max:5120'],
            'delete_photo_ids' => ['nullable', 'array'],
            'delete_photo_ids.*' => ['integer'],
            'photo_order' => ['nullable', 'array'],
            'photo_order.*' => ['integer'],
            'cover_photo_id' => ['nullable', 'integer'],
        ]);

        $validated['pricing_tiers_provided'] = $request->has('pricing_tiers');

        return $validated;
    }

    /** @return array<string, mixed> */
    private function venuePayload(Venue $venue): array
    {
        return [
            'id' => $venue->id,
            'slug' => $venue->slug,
            'category_id' => $venue->category_id,
            'name' => $venue->getTranslations('name'),
            'description' => $venue->getTranslations('description'),
            'size' => $venue->size,
            'capacity' => $venue->capacity,
            'price_from' => $venue->price_from,
            'status' => $venue->status instanceof VenueStatus ? $venue->status->value : $venue->status,
            'amenities' => $venue->amenities ?? [],
            'opening_hours' => $this->normalizeOpeningHours($venue->opening_hours ?? []),
            'latitude' => $venue->latitude,
            'longitude' => $venue->longitude,
            'photos' => $venue->getMedia('images')->sortBy('order_column')->map(fn ($m) => [
                'id' => $m->id,
                'url' => $m->getUrl(),
                'order_column' => $m->order_column,
            ])->values(),
            'sport_ids' => $venue->sportCategories->pluck('id')->all(),
            'pricing_tiers' => $venue->pricingTiers->sortBy('order_column')->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->getTranslations('name'),
                'day_type' => $t->day_type instanceof \BackedEnum ? $t->day_type->value : $t->day_type,
                'specific_day' => $t->specific_day instanceof \BackedEnum ? $t->specific_day->value : $t->specific_day,
                'start_time' => substr((string) $t->start_time, 0, 5),
                'end_time' => substr((string) $t->end_time, 0, 5),
                'duration_minutes' => $t->duration_minutes,
                'price' => $t->price,
                'is_active' => (bool) $t->is_active,
            ])->values(),
        ];
    }

    /**
     * Normalize opening_hours from keyed-object format to indexed-array format.
     * Stored data may be {"saturday":{"open":…}} or [{day:"saturday","open":…}].
     *
     * @param  array<mixed>  $hours
     * @return array<int, array{day:string,open:string,close:string,closed:bool}>
     */
    private function normalizeOpeningHours(array $hours): array
    {
        if (empty($hours)) {
            return [];
        }

        if (array_is_list($hours)) {
            return $hours;
        }

        return collect($hours)
            ->map(fn ($h, $day) => [
                'day' => $day,
                'open' => $h['open'] ?? '08:00',
                'close' => $h['close'] ?? '23:00',
                'closed' => (bool) ($h['closed'] ?? false),
            ])
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function options(): array
    {
        $locale = app()->getLocale();

        return [
            'categories' => $this->categories->query()
                ->where('is_active', true)
                ->orderBy('order_column')
                ->get(['id', 'name'])
                ->map(fn (VenueCategory $c) => ['id' => $c->id, 'name' => $c->getTranslations('name')])
                ->all(),
            'sports' => $this->sports->query()
                ->where('is_active', true)
                ->orderBy('order_column')
                ->get(['id', 'name'])
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->getTranslations('name')])
                ->all(),
            'statuses' => array_map(fn ($c) => $c->value, VenueStatus::cases()),
            'day_types' => array_map(fn ($c) => $c->value, DayType::cases()),
            'days_of_week' => array_map(fn ($c) => $c->value, DayOfWeek::cases()),
        ];
    }
}
