<?php

namespace App\Models\Football;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{
    protected $table = 'football_teams';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_popular' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class, 'league_id');
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_favorite_teams')
            ->withPivot(['display_order', 'notify_matches', 'notify_goals', 'notify_results'])
            ->withTimestamps();
    }

    public function scopePopular(Builder $query): Builder
    {
        return $query->where('is_popular', true)->orderBy('popularity_rank');
    }
}
