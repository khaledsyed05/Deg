<?php

namespace App\Repositories\Eloquent;

use App\Models\VenueFlashDeal;
use App\Repositories\Contracts\VenueFlashDealRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class VenueFlashDealRepository implements VenueFlashDealRepositoryInterface
{
    public function find(int $id): ?VenueFlashDeal
    {
        return VenueFlashDeal::find($id);
    }

    public function findOrFail(int $id): VenueFlashDeal
    {
        return VenueFlashDeal::findOrFail($id);
    }

    public function create(array $data): VenueFlashDeal
    {
        return VenueFlashDeal::create($data);
    }

    public function update(VenueFlashDeal $model, array $data): VenueFlashDeal
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(VenueFlashDeal $model): bool
    {
        return $model->delete();
    }

    public function query(): Builder
    {
        return VenueFlashDeal::query();
    }

    public function findActiveByVenue(int $venueId): Collection
    {
        return VenueFlashDeal::where('venue_id', $venueId)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->orderBy('start_datetime')
            ->get();
    }

    public function findActive(): Collection
    {
        return VenueFlashDeal::where('status', 'active')
            ->where('expires_at', '>', now())
            ->orderBy('start_datetime')
            ->get();
    }

    public function expireStale(): int
    {
        return VenueFlashDeal::where('status', 'active')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);
    }
}
