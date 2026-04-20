<?php

namespace App\Repositories\Contracts;

use App\Models\SavedVenue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface SavedVenueRepositoryInterface
{
    public function find(int $id): ?SavedVenue;

    public function findOrFail(int $id): SavedVenue;

    public function create(array $data): SavedVenue;

    public function update(SavedVenue $model, array $data): SavedVenue;

    public function delete(SavedVenue $model): bool;

    public function query(): Builder;

    public function findByUser(int $userId): Collection;

    public function findForUserAndVenue(int $userId, int $venueId): ?SavedVenue;
}
