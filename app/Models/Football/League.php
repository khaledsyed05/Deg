<?php

namespace App\Models\Football;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class League extends Model
{
    protected $table = 'football_leagues';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'current_season_start' => 'date',
            'current_season_end' => 'date',
            'last_synced_at' => 'datetime',
        ];
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class, 'league_id');
    }

    public function followedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_followed_leagues')
            ->withPivot(['display_order', 'notify_matches'])
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true)->orderBy('display_order');
    }
}
