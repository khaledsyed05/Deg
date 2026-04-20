<?php

namespace App\Repositories\Eloquent;

use App\Enums\VenueStatus;
use App\Models\Venue;
use App\Repositories\Contracts\VenueRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class VenueRepository implements VenueRepositoryInterface
{
    public function find(int $id): ?Venue
    {
        return Venue::find($id);
    }

    public function findOrFail(int $id): Venue
    {
        return Venue::findOrFail($id);
    }

    public function create(array $data): Venue
    {
        return Venue::create($data);
    }

    public function update(Venue $model, array $data): Venue
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(Venue $model): bool
    {
        return $model->delete();
    }

    public function query(): Builder
    {
        return Venue::query();
    }

    public function findByClub(int $clubId): Collection
    {
        return Venue::where('club_id', $clubId)->orderBy('order_column')->get();
    }

    public function findActive(): Collection
    {
        return Venue::where('status', VenueStatus::Active)->orderBy('order_column')->get();
    }

    public function findByCategoryAndCity(int $categoryId, int $cityId): Collection
    {
        return Venue::where('category_id', $categoryId)
            ->where('status', VenueStatus::Active)
            ->whereHas('club', fn (Builder $q) => $q->where('city_id', $cityId))
            ->orderBy('order_column')
            ->get();
    }
}
