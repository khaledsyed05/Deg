<?php

namespace App\Models\Football;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchActivityToken extends Model
{
    protected $guarded = [];

    public const PLATFORM_IOS = 'ios';

    public const PLATFORM_ANDROID = 'android';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForFixture(Builder $query, string $fixtureExternalId): Builder
    {
        return $query->where('fixture_external_id', $fixtureExternalId);
    }

    public function scopeIos(Builder $query): Builder
    {
        return $query->where('platform', self::PLATFORM_IOS);
    }

    public function scopeAndroid(Builder $query): Builder
    {
        return $query->where('platform', self::PLATFORM_ANDROID);
    }
}
