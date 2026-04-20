<?php

namespace App\Repositories\Contracts;

use App\Models\VenueWaitlist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface VenueWaitlistRepositoryInterface
{
    public function find(int $id): ?VenueWaitlist;

    public function findOrFail(int $id): VenueWaitlist;

    public function create(array $data): VenueWaitlist;

    public function update(VenueWaitlist $model, array $data): VenueWaitlist;

    public function delete(VenueWaitlist $model): bool;

    public function query(): Builder;

    public function findByVenueAndDate(int $venueId, string $date, string $startTime): Collection;

    public function findForUser(int $userId): Collection;

    public function deleteExpired(): int;
}
