<?php

namespace App\Repositories\Eloquent;

use App\Models\Settlement;
use App\Repositories\Contracts\SettlementRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SettlementRepository implements SettlementRepositoryInterface
{
    public function find(int $id): ?Settlement
    {
        return Settlement::find($id);
    }

    public function findOrFail(int $id): Settlement
    {
        return Settlement::findOrFail($id);
    }

    public function create(array $data): Settlement
    {
        return Settlement::create($data);
    }

    public function update(Settlement $model, array $data): Settlement
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(Settlement $model): bool
    {
        return $model->delete();
    }

    public function query(): Builder
    {
        return Settlement::query();
    }

    public function findByClub(int $clubId): Collection
    {
        return Settlement::where('club_id', $clubId)->orderByDesc('created_at')->get();
    }

    public function findByStatus(string $status): Collection
    {
        return Settlement::where('status', $status)->orderByDesc('created_at')->get();
    }
}
