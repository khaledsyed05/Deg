<?php

namespace App\Repositories\Contracts;

use App\Models\Club;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface ClubRepositoryInterface
{
    public function find(int $id): ?Club;

    public function findOrFail(int $id): Club;

    public function create(array $data): Club;

    public function update(Club $model, array $data): Club;

    public function delete(Club $model): bool;

    public function query(): Builder;

    public function findActive(): Collection;

    public function findPendingApproval(): Collection;

    public function findByCity(int $cityId): Collection;

    public function findFeatured(): Collection;

    public function approve(Club $club, int $adminId): Club;

    public function reject(Club $club, string $reason): Club;
}
