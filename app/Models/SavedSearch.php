<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedSearch extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'notify_new_venues' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function execute(): Collection
    {
        $query = Venue::query()->active();
        $filters = $this->filters ?? [];

        if (isset($filters['city_id'])) {
            $query->inCity((int) $filters['city_id']);
        }

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['min_price']) || isset($filters['max_price'])) {
            $query->priceRange(
                isset($filters['min_price']) ? (int) $filters['min_price'] : null,
                isset($filters['max_price']) ? (int) $filters['max_price'] : null,
            );
        }

        if (isset($filters['latitude'], $filters['longitude'])) {
            $query->nearby(
                (float) $filters['latitude'],
                (float) $filters['longitude'],
                (float) ($filters['radius_km'] ?? 10),
            );
        }

        return $query->get();
    }
}
