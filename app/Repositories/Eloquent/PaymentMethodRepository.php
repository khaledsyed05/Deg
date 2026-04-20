<?php

namespace App\Repositories\Eloquent;

use App\Models\PaymentMethod;
use App\Repositories\Contracts\PaymentMethodRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PaymentMethodRepository implements PaymentMethodRepositoryInterface
{
    public function find(int $id): ?PaymentMethod
    {
        return PaymentMethod::find($id);
    }

    public function findOrFail(int $id): PaymentMethod
    {
        return PaymentMethod::findOrFail($id);
    }

    public function create(array $data): PaymentMethod
    {
        return PaymentMethod::create($data);
    }

    public function update(PaymentMethod $model, array $data): PaymentMethod
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(PaymentMethod $model): bool
    {
        return $model->delete();
    }

    public function query(): Builder
    {
        return PaymentMethod::query();
    }

    public function findActive(): Collection
    {
        return PaymentMethod::where('is_active', true)->orderBy('order_column')->get();
    }

    public function findByKey(string $providerKey): ?PaymentMethod
    {
        return PaymentMethod::where('provider_key', $providerKey)->first();
    }
}
