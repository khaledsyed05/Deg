<?php

namespace App\Repositories\Contracts;

use App\Models\SportCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface SportCategoryRepositoryInterface
{
    public function find(int $id): ?SportCategory;

    public function findOrFail(int $id): SportCategory;

    public function create(array $data): SportCategory;

    public function update(SportCategory $model, array $data): SportCategory;

    public function delete(SportCategory $model): bool;

    public function query(): Builder;

    public function findActive(): Collection;

    public function findOrdered(): Collection;
}
