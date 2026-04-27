<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DayOfWeek;
use App\Enums\DayType;
use App\Enums\VenueStatus;
use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\Repositories\Contracts\CityRepositoryInterface;
use App\Repositories\Contracts\ClubRepositoryInterface;
use App\Repositories\Contracts\SportCategoryRepositoryInterface;
use App\Repositories\Contracts\VenueCategoryRepositoryInterface;
use App\Repositories\Contracts\VenueRepositoryInterface;
use App\Services\Venue\VenueService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VenueController extends Controller
{
    /** @var array<int, string> */
    private const DEFAULT_AMENITIES = [
        'parking', 'wifi', 'locker_rooms', 'showers', 'cafeteria', 'first_aid', 'lights', 'ac',
    ];

    public function __construct(
        private VenueRepositoryInterface $venues,
        private ClubRepositoryInterface $clubs,
        private CityRepositoryInterface $cities,
        private VenueCategoryRepositoryInterface $categories,
        private SportCategoryRepositoryInterface $sports,
        private VenueService $service,
    ) {}

    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'category_id' => $request->integer('category_id') ?: null,
            'club_id' => $request->integer('club_id') ?: null,
            'city_id' => $request->integer('city_id') ?: null,
            'status' => $request->string('status')->toString(),
            'price_min' => $request->input('price_min'),
            'price_max' => $request->input('price_max'),
        ];

        $venues = $this->venues->query()
            ->with(['club:id,name,city_id', 'club.city:id,name,name_ar', 'category:id,name'])
            ->when($filters['search'], fn ($q, $s) => $q->where(function (Builder $q) use ($s) {
                $q->where('name->ar', 'like', "%{$s}%")
                    ->orWhere('name->en', 'like', "%{$s}%")
                    ->orWhere('description->ar', 'like', "%{$s}%")
                    ->orWhere('description->en', 'like', "%{$s}%")
                    ->orWhereHas('club', fn (Builder $q) => $q->where('name->ar', 'like', "%{$s}%")->orWhere('name->en', 'like', "%{$s}%"));
            }))
            ->when($filters['category_id'], fn ($q, $id) => $q->where('category_id', $id))
            ->when($filters['club_id'], fn ($q, $id) => $q->where('club_id', $id))
            ->when($filters['city_id'], fn ($q, $id) => $q->whereHas('club', fn (Builder $q) => $q->where('city_id', $id)))
            ->when($filters['status'], fn ($q, $s) => $q->where('status', $s))
            ->when($filters['price_min'] !== null && $filters['price_min'] !== '', fn ($q) => $q->where('price_from', '>=', (int) $filters['price_min']))
            ->when($filters['price_max'] !== null && $filters['price_max'] !== '', fn ($q) => $q->where('price_from', '<=', (int) $filters['price_max']))
            ->withCount('bookings')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Venue $v) => $this->indexRow($v));

        return Inertia::render('Admin/Venues/Index', [
            'venues' => $venues,
            'filters' => $filters,
            'stats' => $this->stats(),
            'options' => $this->options(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Venues/Form', [
            'venue' => null,
            'options' => $this->options(),
            'amenities' => self::DEFAULT_AMENITIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateVenue($request);

        $venue = $this->service->createVenue($data);

        return redirect()->route('admin.venues.show', $venue)
            ->with('flash_key', 'venueCreated')
            ->with('flash_type', 'success');
    }

    public function show(Venue $venue): Response
    {
        $venue->load([
            'club:id,name,city_id',
            'club.city:id,name,name_ar',
            'category:id,name',
            'sportCategories:id,name',
            'pricingTiers',
        ]);

        $recent = $venue->bookings()
            ->with('user:id,name,phone_number')
            ->orderByDesc('starts_at')
            ->limit(10)
            ->get(['id', 'user_id', 'venue_id', 'booking_code', 'status', 'starts_at', 'ends_at', 'total_price']);

        $analytics = [
            'total_bookings' => $venue->bookings()->count(),
            'total_revenue' => (int) $venue->bookings()->sum('total_price'),
            'avg_rating' => (float) ($venue->avg_rating ?? 0),
            'occupancy_rate' => $this->service->calculateOccupancyRate($venue),
        ];

        return Inertia::render('Admin/Venues/Show', [
            'venue' => $this->showPayload($venue),
            'recent_bookings' => $recent,
            'analytics' => $analytics,
            'amenities' => self::DEFAULT_AMENITIES,
        ]);
    }

    public function edit(Venue $venue): Response
    {
        $venue->load(['sportCategories:id', 'pricingTiers']);

        return Inertia::render('Admin/Venues/Form', [
            'venue' => $this->showPayload($venue),
            'options' => $this->options(),
            'amenities' => self::DEFAULT_AMENITIES,
        ]);
    }

    public function update(Request $request, Venue $venue): RedirectResponse
    {
        $data = $this->validateVenue($request);

        $this->service->updateVenue($venue, $data);

        return redirect()->route('admin.venues.show', $venue)
            ->with('flash_key', 'venueUpdated')
            ->with('flash_type', 'success');
    }

    public function destroy(Venue $venue): RedirectResponse
    {
        $hasActive = $venue->bookings()
            ->whereIn('status', ['confirmed', 'pending'])
            ->where('starts_at', '>=', now())
            ->exists();

        if ($hasActive) {
            return back()
                ->with('flash_key', 'venueHasActiveBookings')
                ->with('flash_type', 'error');
        }

        $venue->delete();

        return redirect()->route('admin.venues.index')
            ->with('flash_key', 'venueDeleted')
            ->with('flash_type', 'success');
    }

    // ---------- helpers ----------

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
            'club_id' => ['required', 'integer', 'exists:clubs,id'],
            'category_id' => ['nullable', 'integer', 'exists:venue_categories,id'],
            'size' => ['nullable', 'string', 'max:50'],
            'capacity' => ['nullable', 'integer', 'min:1'],
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

        // Signal to the service whether pricing_tiers was provided, so we don't
        // wipe tiers on partial updates that omit the field.
        $validated['pricing_tiers_provided'] = $request->has('pricing_tiers');

        return $validated;
    }

    /** @return array<string, mixed> */
    private function indexRow(Venue $v): array
    {
        return [
            'id' => $v->id,
            'slug' => $v->slug,
            'name' => $v->getTranslations('name'),
            'size' => $v->size,
            'capacity' => $v->capacity,
            'price_from' => $v->price_from,
            'status' => $v->status instanceof VenueStatus ? $v->status->value : $v->status,
            'bookings_count' => $v->bookings_count ?? 0,
            'club' => $v->club ? [
                'id' => $v->club->id,
                'name' => $v->club->getTranslations('name'),
                'city' => $v->club->city ? ['id' => $v->club->city->id, 'name' => $v->club->city->name, 'name_ar' => $v->club->city->name_ar] : null,
            ] : null,
            'category' => $v->category ? ['id' => $v->category->id, 'name' => $v->category->getTranslations('name')] : null,
        ];
    }

    /** @return array<string, mixed> */
    private function showPayload(Venue $venue): array
    {
        return [
            'id' => $venue->id,
            'slug' => $venue->slug,
            'club_id' => $venue->club_id,
            'category_id' => $venue->category_id,
            'name' => $venue->getTranslations('name'),
            'description' => $venue->getTranslations('description'),
            'size' => $venue->size,
            'capacity' => $venue->capacity,
            'price_from' => $venue->price_from,
            'status' => $venue->status instanceof VenueStatus ? $venue->status->value : $venue->status,
            'amenities' => $venue->amenities ?? [],
            'opening_hours' => $venue->opening_hours ?? [],
            'latitude' => $venue->latitude,
            'longitude' => $venue->longitude,
            'avg_rating' => $venue->avg_rating,
            'reviews_count' => $venue->reviews_count,
            'photos' => $venue->getMedia('images')->sortBy('order_column')->map(fn ($m) => [
                'id' => $m->id,
                'url' => $m->getUrl(),
                'order_column' => $m->order_column,
            ])->values(),
            'sport_ids' => $venue->sportCategories->pluck('id')->all(),
            'sport_categories' => $venue->sportCategories->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->getTranslations('name'),
            ])->values(),
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
            'club' => $venue->club ? [
                'id' => $venue->club->id,
                'name' => $venue->club->getTranslations('name'),
                'city' => $venue->club->city ? ['id' => $venue->club->city->id, 'name' => $venue->club->city->name, 'name_ar' => $venue->club->city->name_ar] : null,
            ] : null,
            'category' => $venue->category ? ['id' => $venue->category->id, 'name' => $venue->category->getTranslations('name')] : null,
        ];
    }

    /** @return array{total:int, active:int, inactive:int, suspended:int} */
    private function stats(): array
    {
        $counts = $this->venues->query()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->all();

        return [
            'total' => array_sum($counts),
            'active' => (int) ($counts[VenueStatus::Active->value] ?? 0),
            'inactive' => (int) ($counts[VenueStatus::Inactive->value] ?? 0),
            'suspended' => (int) ($counts[VenueStatus::Suspended->value] ?? 0),
        ];
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private function options(): array
    {
        return [
            'clubs' => $this->clubs->query()
                ->where('status', 'active')
                ->orderBy('name->ar')
                ->get(['id', 'name', 'city_id'])
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->getTranslations('name'), 'city_id' => $c->city_id])
                ->all(),
            'cities' => $this->cities->query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'name_ar'])
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'name_ar' => $c->name_ar])
                ->all(),
            'categories' => $this->categories->query()
                ->where('is_active', true)
                ->orderBy('order_column')
                ->get(['id', 'name'])
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->getTranslations('name')])
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
