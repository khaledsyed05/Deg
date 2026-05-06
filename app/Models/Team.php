<?php

namespace App\Models;

use App\Enums\TeamRole;
use App\Enums\TeamType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'requires_approval' => 'boolean',
            'regular_schedule' => 'array',
            'type' => TeamType::class,
        ];
    }

    protected static function booted(): void
    {
        static::created(function (self $team): void {
            TeamMember::firstOrCreate(
                ['team_id' => $team->id, 'user_id' => $team->captain_id],
                [
                    'role' => TeamRole::Captain->value,
                    'status' => 'active',
                    'joined_at' => now(),
                ],
            );
        });
    }

    public function captain(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captain_id');
    }

    public function sportCategory(): BelongsTo
    {
        return $this->belongsTo(VenueCategory::class, 'sport_category_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function activeMembers(): HasMany
    {
        return $this->members()->where('status', 'active');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function hasMember(int $userId): bool
    {
        return $this->members()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->exists();
    }

    public function isCaptain(int $userId): bool
    {
        return $this->captain_id === $userId;
    }

    public function canInvite(int $userId): bool
    {
        $member = $this->members()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->first();

        if (! $member) {
            return false;
        }

        return in_array($member->role, [TeamRole::Captain->value, TeamRole::Admin->value], true);
    }

    public function isFull(): bool
    {
        return $this->activeMembers()->count() >= $this->max_members;
    }

    public function inviteMember(int $userId, int $invitedBy): TeamMember
    {
        return TeamMember::create([
            'team_id' => $this->id,
            'user_id' => $userId,
            'role' => TeamRole::Member->value,
            'status' => 'invited',
            'invited_by' => $invitedBy,
        ]);
    }

    public function invites(): HasMany
    {
        return $this->hasMany(TeamInvite::class);
    }
}
