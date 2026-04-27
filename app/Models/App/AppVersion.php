<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AppVersion extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_force_update' => 'boolean',
            'is_active' => 'boolean',
            'released_at' => 'date',
        ];
    }

    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform)->where('is_active', true);
    }
}
