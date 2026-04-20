<?php

namespace App\Repositories\Eloquent;

use App\Models\Country;
use App\Repositories\Contracts\CountryRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CountryRepository implements CountryRepositoryInterface
{
    public function find(int $id): ?Country
    {
        return Country::find($id);
    }

    public function findOrFail(int $id): Country
    {
        return Country::findOrFail($id);
    }

    public function create(array $data): Country
    {
        return Country::create($data);
    }

    public function update(Country $model, array $data): Country
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(Country $model): bool
    {
        return $model->delete();
    }

    public function query(): Builder
    {
        return Country::query();
    }

    public function findActive(): Collection
    {
        return Country::where('is_active', true)->orderBy('name')->get();
    }

    public function findByIso2(string $iso2): ?Country
    {
        return Country::where('iso2', $iso2)->first();
    }
}
