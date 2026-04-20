<?php

namespace App\Repositories\Contracts;

use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface WalletTransactionRepositoryInterface
{
    public function find(int $id): ?WalletTransaction;

    public function findOrFail(int $id): WalletTransaction;

    public function create(array $data): WalletTransaction;

    public function update(WalletTransaction $model, array $data): WalletTransaction;

    public function delete(WalletTransaction $model): bool;

    public function query(): Builder;

    public function findByWallet(int $walletId): Collection;

    public function createCredit(int $walletId, int $amount, string $reason, ?int $referenceId = null, ?string $referenceType = null): WalletTransaction;

    public function createDebit(int $walletId, int $amount, string $reason, ?int $referenceId = null, ?string $referenceType = null): WalletTransaction;
}
