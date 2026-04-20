<?php

namespace App\Repositories\Contracts;

use App\Models\VenueFlashDeal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface VenueFlashDealRepositoryInterface
{
    public function find(int $id): ?VenueFlashDeal;

    public function findOrFail(int $id): VenueFlashDeal;

    public function create(array $data): VenueFlashDeal;

    public function update(VenueFlashDeal $model, array $data): VenueFlashDeal;

    public function delete(VenueFlashDeal $model): bool;

    public function query(): Builder;

    public function findActiveByVenue(int $venueId): Collection;

    public function findActive(): Collection;

    public function expireStale(): int;
}
