<?php

namespace App\Repositories\Contracts;

use App\Models\State;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface StateRepositoryInterface
{
    public function find(int $id): ?State;

    public function findOrFail(int $id): State;

    public function create(array $data): State;

    public function update(State $model, array $data): State;

    public function delete(State $model): bool;

    public function query(): Builder;

    public function findByCountry(int $countryId): Collection;
}
