<?php

namespace App\Repositories\Contracts;

use App\Models\Review;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface ReviewRepositoryInterface
{
    public function find(int $id): ?Review;

    public function findOrFail(int $id): Review;

    public function create(array $data): Review;

    public function update(Review $model, array $data): Review;

    public function delete(Review $model): bool;

    public function query(): Builder;

    public function findByClub(int $clubId, bool $publishedOnly = true): Collection;

    public function findByUser(int $userId): Collection;

    public function findPendingModeration(): Collection;
}
