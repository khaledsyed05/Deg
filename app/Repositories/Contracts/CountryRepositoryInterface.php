<?php

namespace App\Repositories\Contracts;

use App\Models\Country;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface CountryRepositoryInterface
{
    public function find(int $id): ?Country;

    public function findOrFail(int $id): Country;

    public function create(array $data): Country;

    public function update(Country $model, array $data): Country;

    public function delete(Country $model): bool;

    public function query(): Builder;

    public function findActive(): Collection;

    public function findByIso2(string $iso2): ?Country;
}
