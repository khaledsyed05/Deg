<?php

namespace App\Observers;

use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Log;

class WalletTransactionObserver
{
    public function created(WalletTransaction $transaction): void
    {
        try {
            $wallet = $transaction->wallet;

            if (! $wallet) {
                return;
            }

            // Integrity check: credit sum minus debit sum should equal stored balance
            $creditSum = $wallet->transactions()->where('type', 'credit')->sum('amount');
            $debitSum = $wallet->transactions()->where('type', 'debit')->sum('amount');
            $calculated = $creditSum - $debitSum;

            if ($wallet->balance !== $calculated) {
                Log::critical('WalletTransactionObserver: balance mismatch detected', [
                    'wallet_id' => $wallet->id,
                    'stored_balance' => $wallet->balance,
                    'calculated_balance' => $calculated,
                    'transaction_id' => $transaction->id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('WalletTransactionObserver: integrity check failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
