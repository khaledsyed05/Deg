<?php

namespace App\Repositories\Contracts;

use App\Models\Wallet;
use Illuminate\Database\Eloquent\Builder;

interface WalletRepositoryInterface
{
    public function find(int $id): ?Wallet;

    public function findOrFail(int $id): Wallet;

    public function create(array $data): Wallet;

    public function update(Wallet $model, array $data): Wallet;

    public function delete(Wallet $model): bool;

    public function query(): Builder;

    public function findByUser(int $userId): ?Wallet;

    public function createForUser(int $userId): Wallet;
}
