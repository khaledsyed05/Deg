<?php

namespace App\Repositories\Eloquent;

use App\Models\Competition;
use App\Repositories\Contracts\CompetitionRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CompetitionRepository implements CompetitionRepositoryInterface
{
    public function find(int $id): ?Competition
    {
        return Competition::find($id);
    }

    public function findOrFail(int $id): Competition
    {
        return Competition::findOrFail($id);
    }

    public function create(array $data): Competition
    {
        return Competition::create($data);
    }

    public function update(Competition $model, array $data): Competition
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(Competition $model): bool
    {
        return $model->delete();
    }

    public function query(): Builder
    {
        return Competition::query();
    }

    public function findPublished(): Collection
    {
        return Competition::where('is_published', true)->orderByDesc('created_at')->get();
    }
}
