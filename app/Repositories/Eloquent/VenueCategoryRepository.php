<?php

namespace App\Repositories\Eloquent;

use App\Models\VenueCategory;
use App\Repositories\Contracts\VenueCategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class VenueCategoryRepository implements VenueCategoryRepositoryInterface
{
    public function find(int $id): ?VenueCategory
    {
        return VenueCategory::find($id);
    }

    public function findOrFail(int $id): VenueCategory
    {
        return VenueCategory::findOrFail($id);
    }

    public function create(array $data): VenueCategory
    {
        return VenueCategory::create($data);
    }

    public function update(VenueCategory $model, array $data): VenueCategory
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(VenueCategory $model): bool
    {
        return $model->delete();
    }

    public function query(): Builder
    {
        return VenueCategory::query();
    }

    public function findActive(): Collection
    {
        return VenueCategory::where('is_active', true)->orderBy('order_column')->get();
    }

    public function findOrdered(): Collection
    {
        return VenueCategory::orderBy('order_column')->get();
    }
}
