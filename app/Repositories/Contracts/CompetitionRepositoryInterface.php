<?php

namespace App\Repositories\Contracts;

use App\Models\Competition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface CompetitionRepositoryInterface
{
    public function find(int $id): ?Competition;

    public function findOrFail(int $id): Competition;

    public function create(array $data): Competition;

    public function update(Competition $model, array $data): Competition;

    public function delete(Competition $model): bool;

    public function query(): Builder;

    public function findPublished(): Collection;
}
