<?php

namespace App\Repositories\Eloquent;

use App\Models\VenueWaitlist;
use App\Repositories\Contracts\VenueWaitlistRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class VenueWaitlistRepository implements VenueWaitlistRepositoryInterface
{
    public function find(int $id): ?VenueWaitlist
    {
        return VenueWaitlist::find($id);
    }

    public function findOrFail(int $id): VenueWaitlist
    {
        return VenueWaitlist::findOrFail($id);
    }

    public function create(array $data): VenueWaitlist
    {
        return VenueWaitlist::create($data);
    }

    public function update(VenueWaitlist $model, array $data): VenueWaitlist
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(VenueWaitlist $model): bool
    {
        return $model->delete();
    }

    public function query(): Builder
    {
        return VenueWaitlist::query();
    }

    public function findByVenueAndDate(int $venueId, string $date, string $startTime): Collection
    {
        return VenueWaitlist::where('venue_id', $venueId)
            ->where('booking_date', $date)
            ->where('start_time', $startTime)
            ->whereNull('notified_at')
            ->where('expires_at', '>', now())
            ->orderBy('created_at')
            ->get();
    }

    public function findForUser(int $userId): Collection
    {
        return VenueWaitlist::where('user_id', $userId)
            ->where('expires_at', '>', now())
            ->orderBy('booking_date')
            ->get();
    }

    public function deleteExpired(): int
    {
        return VenueWaitlist::where('expires_at', '<=', now())->delete();
    }
}
