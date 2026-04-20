<?php

namespace App\Repositories\Eloquent;

use App\Models\City;
use App\Repositories\Contracts\CityRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CityRepository implements CityRepositoryInterface
{
    public function find(int $id): ?City
    {
        return City::find($id);
    }

    public function findOrFail(int $id): City
    {
        return City::findOrFail($id);
    }

    public function create(array $data): City
    {
        return City::create($data);
    }

    public function update(City $model, array $data): City
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(City $model): bool
    {
        return $model->delete();
    }

    public function query(): Builder
    {
        return City::query();
    }

    public function findByState(int $stateId): Collection
    {
        return City::where('state_id', $stateId)->orderBy('name')->get();
    }
}
