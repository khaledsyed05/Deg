<?php

namespace App\Repositories\Contracts;

use App\Models\Settlement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface SettlementRepositoryInterface
{
    public function find(int $id): ?Settlement;

    public function findOrFail(int $id): Settlement;

    public function create(array $data): Settlement;

    public function update(Settlement $model, array $data): Settlement;

    public function delete(Settlement $model): bool;

    public function query(): Builder;

    public function findByClub(int $clubId): Collection;

    public function findByStatus(string $status): Collection;
}
