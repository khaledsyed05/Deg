<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Support\Idempotency;
use Tests\TestCase;

class IdempotencyTest extends TestCase
{
    public function test_same_key_returns_same_transaction_without_re_executing(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::forUser($user);
        $invocations = 0;

        $first = Idempotency::run('key-1', function () use (&$invocations, $wallet) {
            $invocations++;

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'debit',
                'credit_type' => 'booking',
                'amount' => 1000,
                'balance_after' => 0,
                'reason' => 'booking_payment',
                'idempotency_key' => 'key-1',
                'status' => 'completed',
                'created_at' => now(),
            ]);
        });

        $second = Idempotency::run('key-1', function () use (&$invocations) {
            $invocations++;

            $this->fail('Callback should not run a second time for the same key');
        });

        $this->assertSame(1, $invocations);
        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, WalletTransaction::where('idempotency_key', 'key-1')->count());
    }

    public function test_different_keys_execute_independently(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::forUser($user);
        $invocations = 0;

        Idempotency::run('alpha', function () use (&$invocations, $wallet) {
            $invocations++;

            return WalletTransaction::create([
                'wallet_id' => $wallet->id, 'type' => 'debit', 'credit_type' => 'booking',
                'amount' => 100, 'balance_after' => 0, 'reason' => 'booking_payment',
                'idempotency_key' => 'alpha', 'status' => 'completed', 'created_at' => now(),
            ]);
        });

        Idempotency::run('beta', function () use (&$invocations, $wallet) {
            $invocations++;

            return WalletTransaction::create([
                'wallet_id' => $wallet->id, 'type' => 'debit', 'credit_type' => 'booking',
                'amount' => 200, 'balance_after' => 0, 'reason' => 'booking_payment',
                'idempotency_key' => 'beta', 'status' => 'completed', 'created_at' => now(),
            ]);
        });

        $this->assertSame(2, $invocations);
        $this->assertSame(2, WalletTransaction::count());
    }

    public function test_lookup_runs_before_acquiring_the_lock(): void
    {
        // The "fast path" — if a row already exists for this key, we return
        // immediately without taking the cache lock or invoking the closure.
        $user = User::factory()->create();
        $wallet = Wallet::forUser($user);

        $existing = WalletTransaction::create([
            'wallet_id' => $wallet->id, 'type' => 'debit', 'credit_type' => 'booking',
            'amount' => 555, 'balance_after' => 0, 'reason' => 'booking_payment',
            'idempotency_key' => 'fast-path', 'status' => 'completed', 'created_at' => now(),
        ]);

        $result = Idempotency::run('fast-path', function () {
            $this->fail('Callback must not run when an idempotent row already exists');
        });

        $this->assertSame($existing->id, $result->id);
    }
}
