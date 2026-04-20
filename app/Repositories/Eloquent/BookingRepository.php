<?php

namespace App\Repositories\Eloquent;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class BookingRepository implements BookingRepositoryInterface
{
    public function find(int $id): ?Booking
    {
        return Booking::find($id);
    }

    public function findOrFail(int $id): Booking
    {
        return Booking::findOrFail($id);
    }

    public function create(array $data): Booking
    {
        return Booking::create($data);
    }

    public function update(Booking $model, array $data): Booking
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(Booking $model): bool
    {
        return $model->delete();
    }

    public function query(): Builder
    {
        return Booking::query();
    }

    public function findUpcoming(int $userId): Collection
    {
        return Booking::where('user_id', $userId)
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Scheduled])
            ->where('booking_date', '>=', now()->toDateString())
            ->orderBy('starts_at')
            ->get();
    }

    public function findHistory(int $userId): Collection
    {
        return Booking::where('user_id', $userId)
            ->whereIn('status', [BookingStatus::Completed, BookingStatus::Cancelled, BookingStatus::NoShow])
            ->orderByDesc('starts_at')
            ->get();
    }

    public function findByVenueAndDateRange(int $venueId, string $startDate, string $endDate): Collection
    {
        return Booking::where('venue_id', $venueId)
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->whereNotIn('status', [BookingStatus::Cancelled, BookingStatus::Failed])
            ->orderBy('starts_at')
            ->get();
    }

    public function findConfirmedOverlapping(int $venueId, string $date, string $startTime, string $endTime): Collection
    {
        return Booking::where('venue_id', $venueId)
            ->where('booking_date', $date)
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Scheduled])
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->get();
    }

    public function findPendingSettlement(int $clubId, ?string $fromDate = null, ?string $toDate = null): Collection
    {
        return Booking::where('status', BookingStatus::Completed)
            ->whereHas('venue', fn (Builder $q) => $q->where('club_id', $clubId))
            ->whereDoesntHave('venue', fn (Builder $q) => $q->whereHas('club.settlements', function (Builder $s) {}))
            ->when($fromDate, fn (Builder $q) => $q->where('booking_date', '>=', $fromDate))
            ->when($toDate, fn (Builder $q) => $q->where('booking_date', '<=', $toDate))
            ->orderBy('booking_date')
            ->get();
    }
}
