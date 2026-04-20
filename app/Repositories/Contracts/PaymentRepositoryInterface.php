<?php

namespace App\Repositories\Contracts;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface PaymentRepositoryInterface
{
    public function find(int $id): ?Payment;

    public function findOrFail(int $id): Payment;

    public function create(array $data): Payment;

    public function update(Payment $model, array $data): Payment;

    public function delete(Payment $model): bool;

    public function query(): Builder;

    public function findByBooking(int $bookingId): Collection;

    public function findCompletedByBooking(int $bookingId): ?Payment;

    public function findPending(): Collection;
}
