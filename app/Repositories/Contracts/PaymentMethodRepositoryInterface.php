<?php

namespace App\Repositories\Contracts;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface PaymentMethodRepositoryInterface
{
    public function find(int $id): ?PaymentMethod;

    public function findOrFail(int $id): PaymentMethod;

    public function create(array $data): PaymentMethod;

    public function update(PaymentMethod $model, array $data): PaymentMethod;

    public function delete(PaymentMethod $model): bool;

    public function query(): Builder;

    public function findActive(): Collection;

    public function findByKey(string $providerKey): ?PaymentMethod;
}
