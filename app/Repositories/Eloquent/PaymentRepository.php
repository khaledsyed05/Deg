<?php

namespace App\Repositories\Eloquent;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PaymentRepository implements PaymentRepositoryInterface
{
    public function find(int $id): ?Payment
    {
        return Payment::find($id);
    }

    public function findOrFail(int $id): Payment
    {
        return Payment::findOrFail($id);
    }

    public function create(array $data): Payment
    {
        return Payment::create($data);
    }

    public function update(Payment $model, array $data): Payment
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(Payment $model): bool
    {
        return $model->delete();
    }

    public function query(): Builder
    {
        return Payment::query();
    }

    public function findByBooking(int $bookingId): Collection
    {
        return Payment::where('booking_id', $bookingId)->orderByDesc('created_at')->get();
    }

    public function findCompletedByBooking(int $bookingId): ?Payment
    {
        return Payment::where('booking_id', $bookingId)
            ->where('status', PaymentStatus::Completed)
            ->latest()
            ->first();
    }

    public function findPending(): Collection
    {
        return Payment::where('status', PaymentStatus::Pending)->orderBy('created_at')->get();
    }
}
