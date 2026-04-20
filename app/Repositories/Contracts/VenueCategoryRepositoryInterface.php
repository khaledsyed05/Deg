<?php

namespace App\Repositories\Contracts;

use App\Models\VenueCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface VenueCategoryRepositoryInterface
{
    public function find(int $id): ?VenueCategory;

    public function findOrFail(int $id): VenueCategory;

    public function create(array $data): VenueCategory;

    public function update(VenueCategory $model, array $data): VenueCategory;

    public function delete(VenueCategory $model): bool;

    public function query(): Builder;

    public function findActive(): Collection;

    public function findOrdered(): Collection;
}
