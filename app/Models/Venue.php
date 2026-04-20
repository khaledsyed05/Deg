<?php

namespace App\Models;

use App\Enums\VenueStatus;
use Database\Factories\VenueFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class Venue extends Model implements HasMedia, Sortable
{
    /** @use HasFactory<VenueFactory> */
    use HasFactory, HasTranslations, InteractsWithMedia, SoftDeletes, SortableTrait;

    protected $guarded = [];

    /** @var array<int, string> */
    public array $translatable = ['name', 'description'];

    /** @var array<string, mixed> */
    public array $sortable = [
        'order_column_name' => 'order_column',
        'sort_when_creating' => true,
    ];

    protected function casts(): array
    {
        return [
            'amenities' => 'array',
            'opening_hours' => 'array',
            'status' => VenueStatus::class,
            'avg_rating' => 'decimal:2',
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', VenueStatus::Active);
    }
}
