<?php

namespace App\Models\Football;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveMatchState extends Model
{
    protected $table = 'live_match_states';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
            'last_event_at' => 'datetime',
            'last_polled_at' => 'datetime',
        ];
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->whereIn('status', ['LIVE', '1H', '2H', 'HT', 'ET', 'P']);
    }
}
