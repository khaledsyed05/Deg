<?php

namespace App\Repositories\Contracts;

use App\Models\City;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface CityRepositoryInterface
{
    public function find(int $id): ?City;

    public function findOrFail(int $id): City;

    public function create(array $data): City;

    public function update(City $model, array $data): City;

    public function delete(City $model): bool;

    public function query(): Builder;

    public function findByState(int $stateId): Collection;
}
