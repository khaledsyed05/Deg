<?php

namespace App\Repositories\Eloquent;

use App\Models\WalletTransaction;
use App\Repositories\Contracts\WalletTransactionRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class WalletTransactionRepository implements WalletTransactionRepositoryInterface
{
    public function find(int $id): ?WalletTransaction
    {
        return WalletTransaction::find($id);
    }

    public function findOrFail(int $id): WalletTransaction
    {
        return WalletTransaction::findOrFail($id);
    }

    public function create(array $data): WalletTransaction
    {
        return WalletTransaction::create($data);
    }

    public function update(WalletTransaction $model, array $data): WalletTransaction
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(WalletTransaction $model): bool
    {
        return $model->delete();
    }

    public function query(): Builder
    {
        return WalletTransaction::query();
    }

    public function findByWallet(int $walletId): Collection
    {
        return WalletTransaction::where('wallet_id', $walletId)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Pure record creation — balance snapshot must be supplied by caller.
     * WalletService owns the lock + balance mutation.
     */
    public function createCredit(int $walletId, int $amount, string $reason, ?int $referenceId = null, ?string $referenceType = null): WalletTransaction
    {
        return WalletTransaction::create([
            'wallet_id' => $walletId,
            'type' => 'credit',
            'amount' => $amount,
            'balance_after' => 0, // overwritten by WalletService after lock
            'reason' => $reason,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
        ]);
    }

    public function createDebit(int $walletId, int $amount, string $reason, ?int $referenceId = null, ?string $referenceType = null): WalletTransaction
    {
        return WalletTransaction::create([
            'wallet_id' => $walletId,
            'type' => 'debit',
            'amount' => $amount,
            'balance_after' => 0, // overwritten by WalletService after lock
            'reason' => $reason,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
        ]);
    }
}
