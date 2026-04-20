<?php

namespace App\Repositories\Contracts;

use App\Models\SlotReservation;
use Illuminate\Database\Eloquent\Builder;

interface SlotReservationRepositoryInterface
{
    public function find(int $id): ?SlotReservation;

    public function findOrFail(int $id): SlotReservation;

    public function create(array $data): SlotReservation;

    public function update(SlotReservation $model, array $data): SlotReservation;

    public function delete(SlotReservation $model): bool;

    public function query(): Builder;

    public function findActive(int $venueId, string $date, string $startTime): ?SlotReservation;

    public function deleteExpired(): int;

    public function deleteForUser(int $userId): int;
}
