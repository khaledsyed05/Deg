<?php

namespace App\Models;

use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'rating' => 'decimal:1',
            'is_anonymous' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'hidden_at' => 'datetime',
            'club_replied_at' => 'datetime',
            'pros' => 'array',
            'cons' => 'array',
            'photos' => 'array',
            'helpful_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $review): void {
            if (! $review->venue_id && $review->booking_id) {
                $venueId = Booking::whereKey($review->booking_id)->value('venue_id');
                if ($venueId) {
                    $review->venue_id = $venueId;
                }
            }
            if (! $review->club_id && $review->booking_id) {
                $clubId = Booking::whereKey($review->booking_id)
                    ->join('venues', 'venues.id', '=', 'bookings.venue_id')
                    ->value('venues.club_id');
                if ($clubId) {
                    $review->club_id = $clubId;
                }
            }
        });

        static::saved(fn (self $review) => $review->refreshVenueRating());
        static::deleted(fn (self $review) => $review->refreshVenueRating());
    }

    public function clubRepliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'club_replied_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function hiddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hidden_by');
    }

    public function helpfulVotes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'review_helpful_votes')->withTimestamps();
    }

    public function isHelpfulFor(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->helpfulVotes()->where('users.id', $user->id)->exists();
    }

    public function toggleHelpful(User $user): bool
    {
        if ($this->isHelpfulFor($user)) {
            $this->helpfulVotes()->detach($user->id);
            $this->decrement('helpful_count');

            return false;
        }

        $this->helpfulVotes()->attach($user->id);
        $this->increment('helpful_count');

        return true;
    }

    public function canBeEditedBy(?User $user): bool
    {
        if (! $user || $this->user_id !== $user->id) {
            return false;
        }

        return $this->created_at !== null && $this->created_at->copy()->addDays(7)->isFuture();
    }

    protected function refreshVenueRating(): void
    {
        if (! $this->venue_id) {
            return;
        }

        $stats = static::query()
            ->where('venue_id', $this->venue_id)
            ->where('is_published', true)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total_reviews')
            ->first();

        Venue::whereKey($this->venue_id)->update([
            'avg_rating' => round((float) ($stats->avg_rating ?? 0), 2),
        ]);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForVenue(Builder $query, int $venueId): Builder
    {
        return $query->where('venue_id', $venueId);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->latest();
    }

    public function scopeTopRated(Builder $query): Builder
    {
        return $query->orderByDesc('rating');
    }

    public function scopeMostHelpful(Builder $query): Builder
    {
        return $query->orderByDesc('helpful_count');
    }
}
