<?php

namespace App\Repositories\Contracts;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface BookingRepositoryInterface
{
    public function find(int $id): ?Booking;

    public function findOrFail(int $id): Booking;

    public function create(array $data): Booking;

    public function update(Booking $model, array $data): Booking;

    public function delete(Booking $model): bool;

    public function query(): Builder;

    public function findUpcoming(int $userId): Collection;

    public function findHistory(int $userId): Collection;

    public function findByVenueAndDateRange(int $venueId, string $startDate, string $endDate): Collection;

    public function findConfirmedOverlapping(int $venueId, string $date, string $startTime, string $endTime): Collection;

    public function findPendingSettlement(int $clubId, ?string $fromDate = null, ?string $toDate = null): Collection;
}
