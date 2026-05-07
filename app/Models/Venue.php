<?php

namespace App\Models;

use App\Enums\VenueStatus;
use Carbon\Carbon;
use Database\Factories\VenueFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

class Venue extends Model implements HasMedia, Sortable
{
    /** @use HasFactory<VenueFactory> */
    use HasFactory, HasSlug, HasTranslations, InteractsWithMedia, SoftDeletes, SortableTrait;

    protected $guarded = [];

    /** @var array<int, string> */
    public array $translatable = ['name', 'description'];

    /** @var array<string, mixed> */
    public array $sortable = [
        'order_column_name' => 'order_column',
        'sort_when_creating' => true,
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(fn (Venue $model) => $model->getTranslation('name', 'en') ?: $model->getTranslation('name', 'ar') ?: 'venue')
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(100)
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function casts(): array
    {
        return [
            'amenities' => 'array',
            'opening_hours' => 'array',
            'status' => VenueStatus::class,
            'avg_rating' => 'decimal:2',
            'is_featured' => 'boolean',
        ];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(VenueCategory::class, 'category_id');
    }

    public function pricingTiers(): HasMany
    {
        return $this->hasMany(VenuePricingTier::class);
    }

    public function sportCategories(): BelongsToMany
    {
        return $this->belongsToMany(SportCategory::class, 'venue_sport_category');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function slotReservations(): HasMany
    {
        return $this->hasMany(SlotReservation::class);
    }

    public function flashDeals(): HasMany
    {
        return $this->hasMany(VenueFlashDeal::class);
    }

    public function waitlistEntries(): HasMany
    {
        return $this->hasMany(VenueWaitlist::class);
    }

    public function savedByUsers(): HasMany
    {
        return $this->hasMany(SavedVenue::class);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_venues')->withPivot('created_at');
    }

    public function views(): HasMany
    {
        return $this->hasMany(VenueView::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', VenueStatus::Active);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeInCity(Builder $query, int $cityId): Builder
    {
        return $query->whereHas('club', fn (Builder $q) => $q->where('city_id', $cityId));
    }

    public function scopeWithCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopePriceRange(Builder $query, ?int $min, ?int $max): Builder
    {
        if ($min !== null) {
            $query->where('price_from', '>=', $min);
        }
        if ($max !== null) {
            $query->where('price_from', '<=', $max);
        }

        return $query;
    }

    public function scopeSearchTranslated(Builder $query, string $term): Builder
    {
        $like = '%'.$term.'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('name->ar', 'LIKE', $like)
                ->orWhere('name->en', 'LIKE', $like)
                ->orWhere('description->ar', 'LIKE', $like)
                ->orWhere('description->en', 'LIKE', $like);
        });
    }

    /**
     * Filter venues to those inside a lat/lng bounding box. The box is
     * specified by its north/south latitudes and east/west longitudes
     * (inclusive on all four sides — `whereBetween` is inclusive).
     *
     * Portable across MySQL / MariaDB / SQLite — `BETWEEN` semantics
     * for our `decimal(10,8)` / `decimal(11,8)` lat/lng columns are
     * identical across drivers, so no driver branch is required here
     * (unlike `scopeNearby`, which needs trig helpers).
     *
     * Limitation: does NOT handle the antimeridian case (a box that
     * crosses ±180° longitude, where `west > east`). For Syria — and
     * for any single-country viewport — this is fine. If a global
     * deployment ever needs it, split the query into two unioned
     * calls.
     */
    public function scopeWithinBounds(Builder $query, float $north, float $south, float $east, float $west): Builder
    {
        return $query
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [$south, $north])
            ->whereBetween('longitude', [$west, $east]);
    }

    public function scopeNearby(Builder $query, float $latitude, float $longitude, float $radiusKm = 10): Builder
    {
        $query->whereNotNull('latitude')->whereNotNull('longitude');

        $driver = $query->getQuery()->getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            return $query
                ->selectRaw(
                    '*, ( 6371 * acos( cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)) ) ) AS distance_km',
                    [$latitude, $longitude, $latitude],
                )
                ->havingRaw('distance_km <= ?', [$radiusKm])
                ->orderBy('distance_km');
        }

        // Portable bounding-box pre-filter for drivers without trig helpers
        // (SQLite). One degree of latitude is ~111 km; longitude shrinks by
        // cos(latitude). The outer caller can apply a precise Haversine in
        // PHP if it needs sorted distances on these drivers.
        $latDelta = $radiusKm / 111.0;
        $lngDelta = $radiusKm / max(0.0001, 111.0 * cos(deg2rad($latitude)));

        return $query
            ->whereBetween('latitude', [$latitude - $latDelta, $latitude + $latDelta])
            ->whereBetween('longitude', [$longitude - $lngDelta, $longitude + $lngDelta])
            ->orderBy('id');
    }

    public function isOpenNow(?\DateTimeInterface $at = null): bool
    {
        $at = $at ? Carbon::instance($at) : now();
        $day = strtolower($at->format('l'));
        $schedule = $this->opening_hours[$day] ?? null;

        if (! is_array($schedule) || ($schedule['closed'] ?? false)) {
            return false;
        }

        $open = $schedule['open'] ?? null;
        $close = $schedule['close'] ?? null;

        if (! $open || ! $close) {
            return false;
        }

        $now = $at->format('H:i');

        return $now >= $open && $now <= $close;
    }

    public function getIsOpenNowAttribute(): bool
    {
        return $this->isOpenNow();
    }

    public function isFavoritedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->savedByUsers()->where('user_id', $user->id)->exists();
    }

    public function recordView(?User $user, ?string $ipAddress): void
    {
        $this->increment('view_count');

        VenueView::create([
            'user_id' => $user?->id,
            'venue_id' => $this->id,
            'ip_address' => $ipAddress,
            'viewed_at' => now(),
        ]);
    }
}
