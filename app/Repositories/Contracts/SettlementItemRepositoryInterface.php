<?php

namespace App\Repositories\Contracts;

use App\Models\SettlementItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface SettlementItemRepositoryInterface
{
    public function find(int $id): ?SettlementItem;

    public function findOrFail(int $id): SettlementItem;

    public function create(array $data): SettlementItem;

    public function update(SettlementItem $model, array $data): SettlementItem;

    public function delete(SettlementItem $model): bool;

    public function query(): Builder;

    public function findBySettlement(int $settlementId): Collection;
}
