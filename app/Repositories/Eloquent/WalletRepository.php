<?php

namespace App\Repositories\Eloquent;

use App\Models\Wallet;
use App\Repositories\Contracts\WalletRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class WalletRepository implements WalletRepositoryInterface
{
    public function find(int $id): ?Wallet
    {
        return Wallet::find($id);
    }

    public function findOrFail(int $id): Wallet
    {
        return Wallet::findOrFail($id);
    }

    public function create(array $data): Wallet
    {
        return Wallet::create($data);
    }

    public function update(Wallet $model, array $data): Wallet
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(Wallet $model): bool
    {
        return $model->delete();
    }

    public function query(): Builder
    {
        return Wallet::query();
    }

    public function findByUser(int $userId): ?Wallet
    {
        return Wallet::where('user_id', $userId)->first();
    }

    public function createForUser(int $userId): Wallet
    {
        return Wallet::create([
            'user_id' => $userId,
            'balance' => 0,
            'currency' => 'SYP',
        ]);
    }
}
