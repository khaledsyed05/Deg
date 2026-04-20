<?php

namespace App\Repositories\Eloquent;

use App\Models\SlotReservation;
use App\Repositories\Contracts\SlotReservationRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class SlotReservationRepository implements SlotReservationRepositoryInterface
{
    public function find(int $id): ?SlotReservation
    {
        return SlotReservation::find($id);
    }

    public function findOrFail(int $id): SlotReservation
    {
        return SlotReservation::findOrFail($id);
    }

    public function create(array $data): SlotReservation
    {
        return SlotReservation::create($data);
    }

    public function update(SlotReservation $model, array $data): SlotReservation
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(SlotReservation $model): bool
    {
        return $model->delete();
    }

    public function query(): Builder
    {
        return SlotReservation::query();
    }

    public function findActive(int $venueId, string $date, string $startTime): ?SlotReservation
    {
        return SlotReservation::where('venue_id', $venueId)
            ->where('booking_date', $date)
            ->where('start_time', $startTime)
            ->where('reserved_until', '>', now())
            ->first();
    }

    public function deleteExpired(): int
    {
        return SlotReservation::where('reserved_until', '<=', now())->delete();
    }

    public function deleteForUser(int $userId): int
    {
        return SlotReservation::where('user_id', $userId)->delete();
    }
}
