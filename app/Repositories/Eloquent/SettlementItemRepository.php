<?php

namespace App\Repositories\Eloquent;

use App\Models\SettlementItem;
use App\Repositories\Contracts\SettlementItemRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SettlementItemRepository implements SettlementItemRepositoryInterface
{
    public function find(int $id): ?SettlementItem
    {
        return SettlementItem::find($id);
    }

    public function findOrFail(int $id): SettlementItem
    {
        return SettlementItem::findOrFail($id);
    }

    public function create(array $data): SettlementItem
    {
        return SettlementItem::create($data);
    }

    public function update(SettlementItem $model, array $data): SettlementItem
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(SettlementItem $model): bool
    {
        return $model->delete();
    }

    public function query(): Builder
    {
        return SettlementItem::query();
    }

    public function findBySettlement(int $settlementId): Collection
    {
        return SettlementItem::where('settlement_id', $settlementId)->get();
    }
}
