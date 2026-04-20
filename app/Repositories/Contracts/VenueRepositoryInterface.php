<?php

namespace App\Repositories\Contracts;

use App\Models\Venue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface VenueRepositoryInterface
{
    public function find(int $id): ?Venue;

    public function findOrFail(int $id): Venue;

    public function create(array $data): Venue;

    public function update(Venue $model, array $data): Venue;

    public function delete(Venue $model): bool;

    public function query(): Builder;

    public function findByClub(int $clubId): Collection;

    public function findActive(): Collection;

    public function findByCategoryAndCity(int $categoryId, int $cityId): Collection;
}
