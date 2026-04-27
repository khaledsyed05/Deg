<?php

namespace App\Models;

use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'registration_opens_at' => 'datetime',
            'registration_closes_at' => 'datetime',
            'gallery_urls' => 'array',
            'prize_structure' => 'array',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'registration_fee' => 'decimal:2',
        ];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function confirmedRegistrations(): HasMany
    {
        return $this->registrations()->where('status', 'confirmed');
    }

    public function results(): HasMany
    {
        return $this->hasMany(EventResult::class)->orderBy('rank');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>', now())
            ->whereIn('status', ['open', 'closed']);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function getIsRegistrationOpenAttribute(): bool
    {
        return $this->status === 'open'
            && $this->registration_closes_at
            && $this->registration_closes_at->isFuture()
            && (int) $this->current_participants < (int) $this->max_participants;
    }

    public function getRemainingSpotsAttribute(): int
    {
        return max(0, (int) $this->max_participants - (int) $this->current_participants);
    }

    public function isUserRegistered(User $user): bool
    {
        return $this->registrations()
            ->where('user_id', $user->id)
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->exists();
    }

    public function canBeRegisteredBy(User $user): bool
    {
        if (! $this->is_registration_open) {
            return false;
        }

        return ! $this->isUserRegistered($user);
    }
}
