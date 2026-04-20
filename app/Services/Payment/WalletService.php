<?php

namespace App\Services\Payment;

use App\DTOs\Payment\WalletBalanceResult;
use App\Models\Wallet;
use App\Repositories\Contracts\WalletRepositoryInterface;
use App\Repositories\Contracts\WalletTransactionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WalletService
{
    public function __construct(
        private WalletRepositoryInterface $walletRepo,
        private WalletTransactionRepositoryInterface $transactionRepo,
    ) {}

    public function credit(int $userId, int $amount, string $reason, ?int $referenceId = null, ?string $referenceType = null): void
    {
        DB::transaction(function () use ($userId, $amount, $reason, $referenceId, $referenceType) {
            $wallet = $this->walletRepo->findByUser($userId)
                ?? $this->walletRepo->createForUser($userId);

            $locked = Wallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail();
            $balanceAfter = $locked->balance + $amount;

            $transaction = $this->transactionRepo->createCredit(
                $locked->id, $amount, $reason, $referenceId, $referenceType
            );

            $transaction->update(['balance_after' => $balanceAfter]);
            $locked->update(['balance' => $balanceAfter]);
        });
    }

    public function debit(int $userId, int $amount, string $reason, ?int $referenceId = null, ?string $referenceType = null): void
    {
        DB::transaction(function () use ($userId, $amount, $reason, $referenceId, $referenceType) {
            $wallet = $this->walletRepo->findByUser($userId);

            if (! $wallet) {
                throw new RuntimeException('Wallet not found for user.');
            }

            $locked = Wallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail();

            if ($locked->balance < $amount) {
                throw new RuntimeException('Insufficient wallet balance.');
            }

            $balanceAfter = $locked->balance - $amount;

            $transaction = $this->transactionRepo->createDebit(
                $locked->id, $amount, $reason, $referenceId, $referenceType
            );

            $transaction->update(['balance_after' => $balanceAfter]);
            $locked->update(['balance' => $balanceAfter]);
        });
    }

    public function balance(int $userId): WalletBalanceResult
    {
        $wallet = $this->walletRepo->findByUser($userId)
            ?? $this->walletRepo->createForUser($userId);

        return new WalletBalanceResult(
            balance: $wallet->balance,
            currency: $wallet->currency,
            walletId: $wallet->id,
        );
    }
}
