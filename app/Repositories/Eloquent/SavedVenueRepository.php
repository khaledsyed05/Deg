<?php

namespace App\Repositories\Eloquent;

use App\Models\SavedVenue;
use App\Repositories\Contracts\SavedVenueRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SavedVenueRepository implements SavedVenueRepositoryInterface
{
    public function find(int $id): ?SavedVenue
    {
        return SavedVenue::find($id);
    }

    public function findOrFail(int $id): SavedVenue
    {
        return SavedVenue::findOrFail($id);
    }

    public function create(array $data): SavedVenue
    {
        return SavedVenue::create($data);
    }

    public function update(SavedVenue $model, array $data): SavedVenue
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(SavedVenue $model): bool
    {
        return $model->delete();
    }

    public function query(): Builder
    {
        return SavedVenue::query();
    }

    public function findByUser(int $userId): Collection
    {
        return SavedVenue::where('user_id', $userId)->orderByDesc('created_at')->get();
    }

    public function findForUserAndVenue(int $userId, int $venueId): ?SavedVenue
    {
        return SavedVenue::where('user_id', $userId)->where('venue_id', $venueId)->first();
    }
}
