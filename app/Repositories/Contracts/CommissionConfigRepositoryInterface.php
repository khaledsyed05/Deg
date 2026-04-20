<?php

namespace App\Repositories\Contracts;

use App\Models\CommissionConfig;
use Illuminate\Database\Eloquent\Builder;

interface CommissionConfigRepositoryInterface
{
    public function find(int $id): ?CommissionConfig;

    public function findOrFail(int $id): CommissionConfig;

    public function create(array $data): CommissionConfig;

    public function update(CommissionConfig $model, array $data): CommissionConfig;

    public function delete(CommissionConfig $model): bool;

    public function query(): Builder;

    public function findGlobalActive(): ?CommissionConfig;

    public function findForClub(int $clubId): ?CommissionConfig;

    public function findForVenue(int $venueId): ?CommissionConfig;
}
