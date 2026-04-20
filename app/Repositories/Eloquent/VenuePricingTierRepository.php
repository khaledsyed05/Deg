<?php

namespace App\Repositories\Eloquent;

use App\Models\VenuePricingTier;
use App\Repositories\Contracts\VenuePricingTierRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class VenuePricingTierRepository implements VenuePricingTierRepositoryInterface
{
    public function find(int $id): ?VenuePricingTier
    {
        return VenuePricingTier::find($id);
    }

    public function findOrFail(int $id): VenuePricingTier
    {
        return VenuePricingTier::findOrFail($id);
    }

    public function create(array $data): VenuePricingTier
    {
        return VenuePricingTier::create($data);
    }

    public function update(VenuePricingTier $model, array $data): VenuePricingTier
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(VenuePricingTier $model): bool
    {
        return $model->delete();
    }

    public function query(): Builder
    {
        return VenuePricingTier::query();
    }

    public function findActiveByVenue(int $venueId): Collection
    {
        return VenuePricingTier::where('venue_id', $venueId)
            ->where('is_active', true)
            ->orderBy('order_column')
            ->get();
    }
}
