<?php

namespace App\Repositories\Eloquent;

use App\Models\State;
use App\Repositories\Contracts\StateRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class StateRepository implements StateRepositoryInterface
{
    public function find(int $id): ?State
    {
        return State::find($id);
    }

    public function findOrFail(int $id): State
    {
        return State::findOrFail($id);
    }

    public function create(array $data): State
    {
        return State::create($data);
    }

    public function update(State $model, array $data): State
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(State $model): bool
    {
        return $model->delete();
    }

    public function query(): Builder
    {
        return State::query();
    }

    public function findByCountry(int $countryId): Collection
    {
        return State::where('country_id', $countryId)->orderBy('name')->get();
    }
}
