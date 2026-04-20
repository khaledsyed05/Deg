<?php

namespace App\Repositories\Contracts;

use App\Models\VenuePricingTier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface VenuePricingTierRepositoryInterface
{
    public function find(int $id): ?VenuePricingTier;

    public function findOrFail(int $id): VenuePricingTier;

    public function create(array $data): VenuePricingTier;

    public function update(VenuePricingTier $model, array $data): VenuePricingTier;

    public function delete(VenuePricingTier $model): bool;

    public function query(): Builder;

    public function findActiveByVenue(int $venueId): Collection;
}
