<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

class Promotion extends Model
{
    use HasSlug, HasTranslations, LogsActivity;

    protected $guarded = [];

    /** @var array<int, string> */
    public array $translatable = ['name', 'description'];

    protected function casts(): array
    {
        return [
            'valid_from' => 'datetime',
            'valid_to' => 'datetime',
            'allowed_days' => 'array',
            'is_featured' => 'boolean',
            'first_booking_only' => 'boolean',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('code')
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(100)
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('promotion')
            ->logOnly(['code', 'type', 'value', 'status', 'valid_from', 'valid_to'])
            ->logOnlyDirty();
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function venues(): BelongsToMany
    {
        return $this->belongsToMany(Venue::class, 'promotion_venue');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(VenueCategory::class, 'promotion_venue_category');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(PromoUsage::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isExpired(): bool
    {
        return $this->valid_to !== null && $this->valid_to->isPast();
    }

    public function effectiveStatus(): string
    {
        if ($this->isExpired()) {
            return 'expired';
        }

        return $this->status;
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = now();

        if ($this->valid_from && $now->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_to && $now->gt($this->valid_to)) {
            return false;
        }

        if ($this->max_uses !== null && $this->current_uses >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function canBeUsedBy(User $user): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        if ($this->first_booking_only) {
            $hasBookings = Booking::where('user_id', $user->id)
                ->whereIn('status', ['completed', 'confirmed', 'scheduled', 'checked_in'])
                ->exists();

            if ($hasBookings) {
                return false;
            }
        }

        if ($this->max_uses_per_user !== null) {
            $userUsageCount = PromoUsage::where('promotion_id', $this->id)
                ->where('user_id', $user->id)
                ->count();

            if ($userUsageCount >= $this->max_uses_per_user) {
                return false;
            }
        }

        return true;
    }

    public function appliesToVenue(?int $venueId): bool
    {
        if ($this->applies_to === 'all') {
            return true;
        }

        if ($this->venue_id && $this->venue_id === $venueId) {
            return true;
        }

        if ($this->applies_to === 'venues') {
            return $this->venues()->where('venues.id', $venueId)->exists();
        }

        if ($this->applies_to === 'categories' && $venueId) {
            $categoryId = Venue::whereKey($venueId)->value('category_id');

            return $categoryId
                ? $this->categories()->where('venue_categories.id', $categoryId)->exists()
                : false;
        }

        return false;
    }

    public function appliesToDay(?string $date = null): bool
    {
        if (empty($this->allowed_days)) {
            return true;
        }

        $dayOfWeek = strtolower(Carbon::parse($date ?? now())->format('l'));

        return in_array($dayOfWeek, $this->allowed_days, true);
    }

    /**
     * Validate promotion for a prospective booking.
     *
     * @return array{valid: bool, message?: string, promotion?: self, discount_amount?: int, final_amount?: int}
     */
    public function validateForBooking(
        User $user,
        int $venueId,
        int $bookingAmount,
        ?string $bookingDate = null,
    ): array {
        if (! $this->isActive()) {
            return ['valid' => false, 'message' => 'كود الخصم غير صالح أو منتهي الصلاحية'];
        }

        if (! $this->canBeUsedBy($user)) {
            return ['valid' => false, 'message' => 'لا يمكنك استخدام هذا الكود'];
        }

        if (! $this->appliesToVenue($venueId)) {
            return ['valid' => false, 'message' => 'هذا الكود غير صالح لهذا الملعب'];
        }

        if ($this->min_amount !== null && $bookingAmount < $this->min_amount) {
            return [
                'valid' => false,
                'message' => sprintf('الحد الأدنى للحجز %s ل.س', number_format($this->min_amount)),
            ];
        }

        if (! $this->appliesToDay($bookingDate)) {
            return ['valid' => false, 'message' => 'هذا الكود غير صالح لهذا اليوم'];
        }

        $discount = $this->calculateDiscount($bookingAmount);

        return [
            'valid' => true,
            'promotion' => $this,
            'discount_amount' => $discount,
            'final_amount' => max(0, $bookingAmount - $discount),
        ];
    }

    public function calculateDiscount(int $amount): int
    {
        $discount = match ($this->type) {
            'percentage' => (int) floor($amount * $this->value / 100),
            'fixed_amount' => min($this->value, $amount),
            'free_hours' => (int) $this->value,
            default => 0,
        };

        if ($this->max_discount !== null && $discount > $this->max_discount) {
            $discount = $this->max_discount;
        }

        return $discount;
    }

    public function recordUsage(User $user, int $bookingId, int $discountAmount = 0): PromoUsage
    {
        $usage = PromoUsage::create([
            'promotion_id' => $this->id,
            'user_id' => $user->id,
            'booking_id' => $bookingId,
            'discount_amount' => $discountAmount,
        ]);

        $this->increment('current_uses');

        return $usage;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where(function (Builder $q): void {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', now());
            })
            ->where(function (Builder $q): void {
                $q->whereNull('valid_to')->orWhere('valid_to', '>=', now());
            })
            ->where(function (Builder $q): void {
                $q->whereNull('max_uses')->orWhereColumn('current_uses', '<', 'max_uses');
            });
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeForVenue(Builder $query, ?int $venueId): Builder
    {
        return $query->where(function (Builder $q) use ($venueId): void {
            $q->where('applies_to', 'all')
                ->orWhere('venue_id', $venueId)
                ->orWhereHas('venues', fn (Builder $v) => $v->where('venues.id', $venueId));
        });
    }
}
