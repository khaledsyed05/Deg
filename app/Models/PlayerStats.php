<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerStats extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'last_booking_date' => 'date',
            'average_rating_given' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function favoriteSport(): BelongsTo
    {
        return $this->belongsTo(VenueCategory::class, 'favorite_sport_id');
    }

    public function favoriteVenue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'favorite_venue_id');
    }

    public function incrementBookings(): void
    {
        $this->increment('total_bookings');
        $this->increment('bookings_this_month');
        $this->updateStreak();
    }

    public function recordCompletion(int $durationHours, int $amount): void
    {
        $this->increment('completed_bookings');
        $this->increment('total_hours_played', $durationHours);
        $this->increment('total_spent', $amount);
    }

    protected function updateStreak(): void
    {
        $today = now()->toDateString();
        $lastBooking = $this->last_booking_date?->toDateString();

        if (! $lastBooking) {
            $this->streak_days = 1;
        } elseif ($lastBooking === $today) {
            return;
        } elseif ($lastBooking === now()->subDay()->toDateString()) {
            $this->increment('streak_days');
        } else {
            $this->streak_days = 1;
        }

        $this->last_booking_date = $today;
        $this->save();
    }

    public function recalculateFavoriteSport(): void
    {
        $favoriteSport = $this->user->bookings()
            ->join('venues', 'bookings.venue_id', '=', 'venues.id')
            ->selectRaw('venues.category_id, COUNT(*) as count')
            ->groupBy('venues.category_id')
            ->orderByDesc('count')
            ->first();

        if ($favoriteSport) {
            $this->update(['favorite_sport_id' => $favoriteSport->category_id]);
        }
    }

    public function recalculateFavoriteVenue(): void
    {
        $favoriteVenue = $this->user->bookings()
            ->selectRaw('venue_id, COUNT(*) as count')
            ->groupBy('venue_id')
            ->orderByDesc('count')
            ->first();

        if ($favoriteVenue) {
            $this->update(['favorite_venue_id' => $favoriteVenue->venue_id]);
        }
    }
}
