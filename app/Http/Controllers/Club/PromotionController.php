<?php

namespace App\Http\Controllers\Club;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Club;
use App\Models\Promotion;
use App\Models\Venue;
use App\Models\VenueCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PromotionController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $filters = [
            'status' => $request->string('status')->toString(),
            'venue_slug' => $request->string('venue_slug')->toString(),
            'type' => $request->string('type')->toString(),
        ];

        $now = now();
        $locale = app()->getLocale();

        $activeCount = Promotion::where('club_id', $club->id)
            ->where('status', 'active')
            ->where('valid_from', '<=', $now)
            ->where(fn (Builder $q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', $now))
            ->count();

        $totalUses = (int) Promotion::where('club_id', $club->id)->sum('current_uses');

        $query = Promotion::query()
            ->where('club_id', $club->id);

        if ($filters['status']) {
            if ($filters['status'] === 'expired') {
                $query->whereNotNull('valid_to')->where('valid_to', '<', $now);
            } else {
                $query->where('status', $filters['status']);
                if ($filters['status'] === 'active') {
                    $query->where(fn (Builder $q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', $now));
                }
            }
        }

        if ($filters['venue_slug']) {
            $query->whereHas('venues', fn (Builder $q) => $q->where('slug', $filters['venue_slug']));
        }

        if ($filters['type']) {
            $query->where('type', $filters['type']);
        }

        $promotions = $query
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Promotion $p) => [
                'id' => $p->id,
                'slug' => $p->slug,
                'code' => $p->code,
                'name' => $p->getTranslation('name', $locale) ?: $p->getTranslation('name', 'ar'),
                'type' => $p->type,
                'value' => (int) $p->value,
                'min_amount' => $p->min_amount !== null ? (int) $p->min_amount : null,
                'max_discount' => $p->max_discount !== null ? (int) $p->max_discount : null,
                'valid_from' => $p->valid_from?->toDateString(),
                'valid_to' => $p->valid_to?->toDateString(),
                'current_uses' => (int) $p->current_uses,
                'max_uses' => $p->max_uses !== null ? (int) $p->max_uses : null,
                'max_uses_per_user' => $p->max_uses_per_user !== null ? (int) $p->max_uses_per_user : null,
                'applies_to' => $p->applies_to,
                'status' => $p->effectiveStatus(),
            ]);

        return Inertia::render('Club/Promotions/Index', [
            'stats' => [
                'active_count' => $activeCount,
                'total_uses' => $totalUses,
                'revenue_impact' => 0,
            ],
            'promotions' => $promotions,
            'filters' => $filters,
            'venues' => $club->venues()
                ->orderBy('name->ar')
                ->get(['id', 'slug', 'name'])
                ->map(fn (Venue $v) => [
                    'slug' => $v->slug,
                    'name' => $v->getTranslation('name', $locale) ?: $v->getTranslation('name', 'ar'),
                ]),
        ]);
    }

    public function create(): Response|RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $locale = app()->getLocale();

        return Inertia::render('Club/Promotions/Create', [
            'venues' => $club->venues()
                ->orderBy('name->ar')
                ->get(['id', 'slug', 'name'])
                ->map(fn (Venue $v) => [
                    'slug' => $v->slug,
                    'name' => $v->getTranslation('name', $locale) ?: $v->getTranslation('name', 'ar'),
                ]),
            'categories' => VenueCategory::query()
                ->where('is_active', true)
                ->orderBy('order_column')
                ->get(['id', 'slug', 'name'])
                ->map(fn (VenueCategory $c) => [
                    'slug' => $c->slug,
                    'name' => $c->getTranslation('name', $locale) ?: $c->getTranslation('name', 'ar'),
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:promotions,code'],
            'name' => ['required', 'array'],
            'name.ar' => ['required', 'string', 'max:255'],
            'name.en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'description.ar' => ['nullable', 'string', 'max:1000'],
            'description.en' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'numeric', 'min:0'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'valid_from' => ['required', 'date'],
            'valid_to' => ['nullable', 'date', 'after:valid_from'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'max_uses_per_user' => ['nullable', 'integer', 'min:1'],
            'applies_to' => ['required', 'in:all,venues,categories'],
            'venue_slugs' => ['required_if:applies_to,venues', 'array'],
            'venue_slugs.*' => ['string', 'exists:venues,slug'],
            'category_slugs' => ['required_if:applies_to,categories', 'array'],
            'category_slugs.*' => ['string', 'exists:venue_categories,slug'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        if ($validated['type'] === 'percentage' && $validated['value'] > 100) {
            return back()
                ->withErrors(['value' => 'النسبة المئوية يجب أن تكون بين 0 و 100'])
                ->withInput();
        }

        $promotion = Promotion::create([
            'club_id' => $club->id,
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'value' => (int) round($validated['value']),
            'min_amount' => $validated['min_amount'] !== null ? (int) round($validated['min_amount']) : null,
            'max_discount' => $validated['max_discount'] !== null ? (int) round($validated['max_discount']) : null,
            'valid_from' => $validated['valid_from'],
            'valid_to' => $validated['valid_to'] ?? null,
            'max_uses' => $validated['max_uses'] ?? null,
            'max_uses_per_user' => $validated['max_uses_per_user'] ?? null,
            'current_uses' => 0,
            'applies_to' => $validated['applies_to'],
            'status' => $validated['status'],
            'created_by' => Auth::id(),
        ]);

        if ($validated['applies_to'] === 'venues') {
            $venueIds = Venue::where('club_id', $club->id)
                ->whereIn('slug', $validated['venue_slugs'])
                ->pluck('id');
            $promotion->venues()->attach($venueIds);
        } elseif ($validated['applies_to'] === 'categories') {
            $categoryIds = VenueCategory::whereIn('slug', $validated['category_slugs'])->pluck('id');
            $promotion->categories()->attach($categoryIds);
        }

        activity()
            ->performedOn($promotion)
            ->causedBy(Auth::user())
            ->withProperties([
                'code' => $promotion->code,
                'type' => $promotion->type,
                'value' => $promotion->value,
            ])
            ->log('club_promotion_created');

        return redirect()
            ->route('club.promotions.index')
            ->with('flash_key', 'promotionCreated')
            ->with('flash_type', 'success');
    }

    public function show(Promotion $promotion): Response|RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $this->ensureOwnership($promotion, $club);

        $locale = app()->getLocale();

        $promotion->load([
            'venues:id,slug,name',
            'categories:id,slug,name',
            'creator:id,name',
        ]);

        $bookingsQuery = Booking::where('promotion_id', $promotion->id);
        $completedQuery = (clone $bookingsQuery)->where('status', 'completed');

        $totalUses = (int) (clone $bookingsQuery)->count();
        $uniqueUsers = (int) (clone $completedQuery)->distinct('user_id')->count('user_id');
        $avgDiscount = (int) round((float) (clone $completedQuery)->avg('discount_amount'));

        $usageTimeline = (clone $bookingsQuery)
            ->where('booking_date', '>=', now()->subDays(30)->toDateString())
            ->selectRaw('DATE(booking_date) as date, COUNT(*) as uses')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => (string) $row->date,
                'uses' => (int) $row->uses,
            ]);

        $topUsers = (clone $completedQuery)
            ->with('user:id,name')
            ->selectRaw('user_id, COUNT(*) as times_used, COALESCE(SUM(discount_amount), 0) as total_discount')
            ->groupBy('user_id')
            ->orderByDesc('times_used')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'user' => $row->user?->name ?? '—',
                'times_used' => (int) $row->times_used,
                'total_discount' => (int) $row->total_discount,
            ]);

        $bookings = Booking::query()
            ->with(['venue:id,slug,name', 'user:id,name'])
            ->where('promotion_id', $promotion->id)
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
                    'name' => $b->venue?->getTranslation('name', $locale)
                        ?: $b->venue?->getTranslation('name', 'ar'),
                ],
                'player' => $b->user?->name ?? '—',
                'original_price' => (int) $b->total_price + (int) $b->discount_amount,
                'discount_amount' => (int) $b->discount_amount,
                'final_price' => (int) $b->total_price,
                'status' => $b->status instanceof BookingStatus ? $b->status->value : $b->status,
            ]);

        return Inertia::render('Club/Promotions/Show', [
            'promotion' => [
                'id' => $promotion->id,
                'slug' => $promotion->slug,
                'code' => $promotion->code,
                'name' => [
                    'ar' => $promotion->getTranslation('name', 'ar'),
                    'en' => $promotion->getTranslation('name', 'en'),
                ],
                'description' => [
                    'ar' => $promotion->getTranslation('description', 'ar'),
                    'en' => $promotion->getTranslation('description', 'en'),
                ],
                'type' => $promotion->type,
                'value' => (int) $promotion->value,
                'min_amount' => $promotion->min_amount !== null ? (int) $promotion->min_amount : null,
                'max_discount' => $promotion->max_discount !== null ? (int) $promotion->max_discount : null,
                'valid_from' => $promotion->valid_from?->toIso8601String(),
                'valid_to' => $promotion->valid_to?->toIso8601String(),
                'current_uses' => (int) $promotion->current_uses,
                'max_uses' => $promotion->max_uses !== null ? (int) $promotion->max_uses : null,
                'max_uses_per_user' => $promotion->max_uses_per_user !== null ? (int) $promotion->max_uses_per_user : null,
                'status' => $promotion->effectiveStatus(),
                'applies_to' => $promotion->applies_to,
                'venues' => $promotion->venues->map(fn ($v) => [
                    'slug' => $v->slug,
                    'name' => $v->getTranslation('name', $locale) ?: $v->getTranslation('name', 'ar'),
                ])->values(),
                'categories' => $promotion->categories->map(fn ($c) => [
                    'slug' => $c->slug,
                    'name' => $c->getTranslation('name', $locale) ?: $c->getTranslation('name', 'ar'),
                ])->values(),
                'created_at' => $promotion->created_at?->toIso8601String(),
                'created_by' => $promotion->creator?->name,
            ],
            'analytics' => [
                'total_uses' => $totalUses,
                'unique_users' => $uniqueUsers,
                'avg_discount' => $avgDiscount,
            ],
            'usageTimeline' => $usageTimeline,
            'topUsers' => $topUsers,
            'bookings' => $bookings,
        ]);
    }

    public function edit(Promotion $promotion): Response|RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $this->ensureOwnership($promotion, $club);

        $locale = app()->getLocale();

        $promotion->load(['venues:id,slug', 'categories:id,slug']);

        return Inertia::render('Club/Promotions/Edit', [
            'promotion' => [
                'id' => $promotion->id,
                'slug' => $promotion->slug,
                'code' => $promotion->code,
                'name' => [
                    'ar' => $promotion->getTranslation('name', 'ar'),
                    'en' => $promotion->getTranslation('name', 'en'),
                ],
                'description' => [
                    'ar' => $promotion->getTranslation('description', 'ar'),
                    'en' => $promotion->getTranslation('description', 'en'),
                ],
                'type' => $promotion->type,
                'value' => (int) $promotion->value,
                'min_amount' => $promotion->min_amount !== null ? (int) $promotion->min_amount : null,
                'max_discount' => $promotion->max_discount !== null ? (int) $promotion->max_discount : null,
                'valid_from' => $promotion->valid_from?->toDateString(),
                'valid_to' => $promotion->valid_to?->toDateString(),
                'max_uses' => $promotion->max_uses !== null ? (int) $promotion->max_uses : null,
                'max_uses_per_user' => $promotion->max_uses_per_user !== null ? (int) $promotion->max_uses_per_user : null,
                'applies_to' => $promotion->applies_to,
                'venue_slugs' => $promotion->venues->pluck('slug')->all(),
                'category_slugs' => $promotion->categories->pluck('slug')->all(),
                'status' => $promotion->status,
                'current_uses' => (int) $promotion->current_uses,
            ],
            'venues' => $club->venues()
                ->orderBy('name->ar')
                ->get(['id', 'slug', 'name'])
                ->map(fn (Venue $v) => [
                    'slug' => $v->slug,
                    'name' => $v->getTranslation('name', $locale) ?: $v->getTranslation('name', 'ar'),
                ]),
            'categories' => VenueCategory::query()
                ->where('is_active', true)
                ->orderBy('order_column')
                ->get(['id', 'slug', 'name'])
                ->map(fn (VenueCategory $c) => [
                    'slug' => $c->slug,
                    'name' => $c->getTranslation('name', $locale) ?: $c->getTranslation('name', 'ar'),
                ]),
        ]);
    }

    public function update(Request $request, Promotion $promotion): RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $this->ensureOwnership($promotion, $club);

        $validated = $request->validate([
            'name' => ['required', 'array'],
            'name.ar' => ['required', 'string', 'max:255'],
            'name.en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'description.ar' => ['nullable', 'string', 'max:1000'],
            'description.en' => ['nullable', 'string', 'max:1000'],
            'value' => ['required', 'numeric', 'min:0'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'valid_from' => ['required', 'date'],
            'valid_to' => ['nullable', 'date', 'after:valid_from'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'max_uses_per_user' => ['nullable', 'integer', 'min:1'],
            'applies_to' => ['required', 'in:all,venues,categories'],
            'venue_slugs' => ['required_if:applies_to,venues', 'array'],
            'venue_slugs.*' => ['string', 'exists:venues,slug'],
            'category_slugs' => ['required_if:applies_to,categories', 'array'],
            'category_slugs.*' => ['string', 'exists:venue_categories,slug'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        if ($promotion->type === 'percentage' && $validated['value'] > 100) {
            return back()
                ->withErrors(['value' => 'النسبة المئوية يجب أن تكون بين 0 و 100'])
                ->withInput();
        }

        if ((int) $promotion->max_uses !== null && $validated['max_uses'] !== null
            && (int) $validated['max_uses'] < (int) $promotion->current_uses) {
            return back()
                ->withErrors(['max_uses' => 'لا يمكن تقليل الحد الأقصى للاستخدام عن العدد الحالي'])
                ->withInput();
        }

        $promotion->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'value' => (int) round($validated['value']),
            'min_amount' => $validated['min_amount'] !== null ? (int) round($validated['min_amount']) : null,
            'max_discount' => $validated['max_discount'] !== null ? (int) round($validated['max_discount']) : null,
            'valid_from' => $validated['valid_from'],
            'valid_to' => $validated['valid_to'] ?? null,
            'max_uses' => $validated['max_uses'] ?? null,
            'max_uses_per_user' => $validated['max_uses_per_user'] ?? null,
            'applies_to' => $validated['applies_to'],
            'status' => $validated['status'],
        ]);

        if ($validated['applies_to'] === 'venues') {
            $venueIds = Venue::where('club_id', $club->id)
                ->whereIn('slug', $validated['venue_slugs'])
                ->pluck('id');
            $promotion->venues()->sync($venueIds);
        } else {
            $promotion->venues()->detach();
        }

        if ($validated['applies_to'] === 'categories') {
            $categoryIds = VenueCategory::whereIn('slug', $validated['category_slugs'])->pluck('id');
            $promotion->categories()->sync($categoryIds);
        } else {
            $promotion->categories()->detach();
        }

        activity()
            ->performedOn($promotion)
            ->causedBy(Auth::user())
            ->log('club_promotion_updated');

        return redirect()
            ->route('club.promotions.show', $promotion->slug)
            ->with('flash_key', 'promotionUpdated')
            ->with('flash_type', 'success');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $this->ensureOwnership($promotion, $club);

        if ((int) $promotion->current_uses > 0) {
            return back()
                ->with('flash_key', 'cannotDeleteUsedPromotion')
                ->with('flash_type', 'error');
        }

        $code = $promotion->code;
        $promotion->delete();

        activity()
            ->causedBy(Auth::user())
            ->withProperties(['code' => $code])
            ->log('club_promotion_deleted');

        return redirect()
            ->route('club.promotions.index')
            ->with('flash_key', 'promotionDeleted')
            ->with('flash_type', 'success');
    }

    public function bulkCreate(Request $request): StreamedResponse|RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $validated = $request->validate([
            'prefix' => ['required', 'string', 'max:20', 'alpha_dash'],
            'count' => ['required', 'integer', 'min:1', 'max:500'],
            'name' => ['required', 'array'],
            'name.ar' => ['required', 'string', 'max:255'],
            'name.en' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'numeric', 'min:0'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'valid_from' => ['required', 'date'],
            'valid_to' => ['nullable', 'date', 'after:valid_from'],
            'max_uses_per_code' => ['required', 'integer', 'min:1'],
            'applies_to' => ['required', 'in:all,venues,categories'],
            'venue_slugs' => ['required_if:applies_to,venues', 'array'],
            'venue_slugs.*' => ['string', 'exists:venues,slug'],
            'category_slugs' => ['required_if:applies_to,categories', 'array'],
            'category_slugs.*' => ['string', 'exists:venue_categories,slug'],
        ]);

        if ($validated['type'] === 'percentage' && $validated['value'] > 100) {
            return back()
                ->withErrors(['value' => 'النسبة المئوية يجب أن تكون بين 0 و 100'])
                ->withInput();
        }

        $prefix = strtoupper($validated['prefix']);
        $count = (int) $validated['count'];

        $venueIds = collect();
        if ($validated['applies_to'] === 'venues') {
            $venueIds = Venue::where('club_id', $club->id)
                ->whereIn('slug', $validated['venue_slugs'])
                ->pluck('id');
        }

        $categoryIds = collect();
        if ($validated['applies_to'] === 'categories') {
            $categoryIds = VenueCategory::whereIn('slug', $validated['category_slugs'])->pluck('id');
        }

        try {
            $promotions = DB::transaction(function () use (
                $validated, $club, $prefix, $count, $venueIds, $categoryIds
            ) {
                $created = [];
                $suffix = 1;

                for ($i = 1; $i <= $count; $i++) {
                    // Find next free code (skip existing suffixes).
                    do {
                        $code = $prefix.str_pad((string) $suffix, 3, '0', STR_PAD_LEFT);
                        $suffix++;
                    } while (Promotion::where('code', $code)->exists());

                    $promotion = Promotion::create([
                        'club_id' => $club->id,
                        'code' => $code,
                        'name' => $validated['name'],
                        'description' => null,
                        'type' => $validated['type'],
                        'value' => (int) round($validated['value']),
                        'min_amount' => $validated['min_amount'] !== null ? (int) round($validated['min_amount']) : null,
                        'max_discount' => $validated['max_discount'] !== null ? (int) round($validated['max_discount']) : null,
                        'valid_from' => $validated['valid_from'],
                        'valid_to' => $validated['valid_to'] ?? null,
                        'max_uses' => (int) $validated['max_uses_per_code'],
                        'max_uses_per_user' => 1,
                        'current_uses' => 0,
                        'applies_to' => $validated['applies_to'],
                        'status' => 'active',
                        'created_by' => Auth::id(),
                    ]);

                    if ($venueIds->isNotEmpty()) {
                        $promotion->venues()->attach($venueIds);
                    }
                    if ($categoryIds->isNotEmpty()) {
                        $promotion->categories()->attach($categoryIds);
                    }

                    $created[] = $promotion;
                }

                return $created;
            });
        } catch (\Throwable) {
            return back()
                ->with('flash_key', 'bulkCreationFailed')
                ->with('flash_type', 'error');
        }

        activity()
            ->causedBy(Auth::user())
            ->withProperties([
                'prefix' => $prefix,
                'count' => count($promotions),
            ])
            ->log('club_promotion_bulk_created');

        $filename = 'bulk_codes_'.$prefix.'_'.now()->format('Y-m-d_His').'.csv';

        return response()->stream(function () use ($promotions) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['الرمز', 'النوع', 'القيمة', 'صالح من', 'صالح حتى', 'الحد الأقصى للاستخدام']);

            foreach ($promotions as $p) {
                fputcsv($out, [
                    $p->code,
                    $p->type,
                    $p->value,
                    $p->valid_from?->toDateString(),
                    $p->valid_to?->toDateString() ?? '∞',
                    $p->max_uses,
                ]);
            }
            fclose($out);
        }, HttpResponse::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function clone(Promotion $promotion): Response|RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $this->ensureOwnership($promotion, $club);

        $promotion->load(['venues:id,slug', 'categories:id,slug']);

        // Generate next available code with -COPY[n] suffix
        $base = preg_replace('/-COPY\d*$/', '', $promotion->code);
        $newCode = $base.'-COPY';
        $n = 1;
        while (Promotion::where('code', $newCode)->exists()) {
            $n++;
            $newCode = $base.'-COPY'.$n;
        }

        $locale = app()->getLocale();

        return Inertia::render('Club/Promotions/Create', [
            'venues' => $club->venues()
                ->orderBy('name->ar')
                ->get(['id', 'slug', 'name'])
                ->map(fn (Venue $v) => [
                    'slug' => $v->slug,
                    'name' => $v->getTranslation('name', $locale) ?: $v->getTranslation('name', 'ar'),
                ]),
            'categories' => VenueCategory::query()
                ->where('is_active', true)
                ->orderBy('order_column')
                ->get(['id', 'slug', 'name'])
                ->map(fn (VenueCategory $c) => [
                    'slug' => $c->slug,
                    'name' => $c->getTranslation('name', $locale) ?: $c->getTranslation('name', 'ar'),
                ]),
            'clone' => [
                'source_code' => $promotion->code,
                'code' => $newCode,
                'name' => [
                    'ar' => $promotion->getTranslation('name', 'ar'),
                    'en' => $promotion->getTranslation('name', 'en'),
                ],
                'description' => [
                    'ar' => $promotion->getTranslation('description', 'ar'),
                    'en' => $promotion->getTranslation('description', 'en'),
                ],
                'type' => $promotion->type,
                'value' => (int) $promotion->value,
                'min_amount' => $promotion->min_amount !== null ? (int) $promotion->min_amount : null,
                'max_discount' => $promotion->max_discount !== null ? (int) $promotion->max_discount : null,
                'valid_from' => now()->toDateString(),
                'valid_to' => $promotion->valid_to
                    ? $promotion->valid_to->copy()->addDays(7)->toDateString()
                    : null,
                'max_uses' => $promotion->max_uses !== null ? (int) $promotion->max_uses : null,
                'max_uses_per_user' => $promotion->max_uses_per_user !== null ? (int) $promotion->max_uses_per_user : null,
                'applies_to' => $promotion->applies_to,
                'venue_slugs' => $promotion->venues->pluck('slug')->all(),
                'category_slugs' => $promotion->categories->pluck('slug')->all(),
            ],
        ]);
    }

    public function getExpiringSoon(): JsonResponse|RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $now = now();
        $threshold = $now->copy()->addDays(3);

        $items = Promotion::where('club_id', $club->id)
            ->where('status', 'active')
            ->whereNotNull('valid_to')
            ->whereBetween('valid_to', [$now, $threshold])
            ->orderBy('valid_to')
            ->get(['id', 'slug', 'code', 'valid_to'])
            ->map(fn (Promotion $p) => [
                'slug' => $p->slug,
                'code' => $p->code,
                'expires_at' => $p->valid_to?->toDateString(),
                'days_left' => (int) max(0, $now->diffInDays($p->valid_to, false)),
            ])
            ->values();

        return response()->json($items);
    }

    private function ensureOwnership(Promotion $promotion, Club $club): void
    {
        if ((int) $promotion->club_id !== (int) $club->id) {
            abort(403, 'غير مخوّل للوصول إلى هذا العرض.');
        }
    }

    public function toggleStatus(Promotion $promotion): RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $this->ensureOwnership($promotion, $club);

        if ($promotion->isExpired()) {
            return back()
                ->with('flash_key', 'promotionExpiredCannotToggle')
                ->with('flash_type', 'error');
        }

        $newStatus = $promotion->status === 'active' ? 'inactive' : 'active';
        $promotion->update(['status' => $newStatus]);

        activity()
            ->performedOn($promotion)
            ->causedBy(Auth::user())
            ->log("club_promotion_{$newStatus}");

        return back()
            ->with('flash_key', $newStatus === 'active' ? 'promotionActivated' : 'promotionDeactivated')
            ->with('flash_type', 'success');
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
