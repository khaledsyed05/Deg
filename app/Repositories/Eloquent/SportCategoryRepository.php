<?php

namespace App\Repositories\Eloquent;

use App\Models\SportCategory;
use App\Repositories\Contracts\SportCategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SportCategoryRepository implements SportCategoryRepositoryInterface
{
    public function find(int $id): ?SportCategory
    {
        return SportCategory::find($id);
    }

    public function findOrFail(int $id): SportCategory
    {
        return SportCategory::findOrFail($id);
    }

    public function create(array $data): SportCategory
    {
        return SportCategory::create($data);
    }

    public function update(SportCategory $model, array $data): SportCategory
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(SportCategory $model): bool
    {
        return $model->delete();
    }

    public function query(): Builder
    {
        return SportCategory::query();
    }

    public function findActive(): Collection
    {
        return SportCategory::where('is_active', true)->orderBy('order_column')->get();
    }

    public function findOrdered(): Collection
    {
        return SportCategory::orderBy('order_column')->get();
    }
}
