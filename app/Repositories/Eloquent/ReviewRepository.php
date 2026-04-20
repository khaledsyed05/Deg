<?php

namespace App\Repositories\Eloquent;

use App\Models\Review;
use App\Repositories\Contracts\ReviewRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ReviewRepository implements ReviewRepositoryInterface
{
    public function find(int $id): ?Review
    {
        return Review::find($id);
    }

    public function findOrFail(int $id): Review
    {
        return Review::findOrFail($id);
    }

    public function create(array $data): Review
    {
        return Review::create($data);
    }

    public function update(Review $model, array $data): Review
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(Review $model): bool
    {
        return $model->delete();
    }

    public function query(): Builder
    {
        return Review::query();
    }

    public function findByClub(int $clubId, bool $publishedOnly = true): Collection
    {
        return Review::where('club_id', $clubId)
            ->when($publishedOnly, fn (Builder $q) => $q->where('is_published', true))
            ->orderByDesc('created_at')
            ->get();
    }

    public function findByUser(int $userId): Collection
    {
        return Review::where('user_id', $userId)->orderByDesc('created_at')->get();
    }

    public function findPendingModeration(): Collection
    {
        return Review::where('is_published', false)
            ->whereNull('hidden_at')
            ->orderBy('created_at')
            ->get();
    }
}
