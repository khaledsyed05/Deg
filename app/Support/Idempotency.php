<?php

namespace App\Support;

use App\Models\WalletTransaction;
use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Run a closure under an idempotency lock keyed by an external key.
 *
 * Two safeguards combined:
 *
 *   1. A short-lived `Cache::lock` blocks concurrent attempts within the
 *      same process / cluster window. Same-key callers either share the
 *      result (if one completed first) or are serialized.
 *   2. A unique index on `wallet_transactions.idempotency_key` (added
 *      in the Sprint 3 B1 migration) is the durable backstop — even if
 *      the cache lock is evicted, a duplicate insert fails fast.
 *
 * If a transaction already exists with this key, the previous result
 * is returned WITHOUT re-running the callback. This makes pay-booking
 * (and any other wallet write) safe to retry.
 */
class Idempotency
{
    /**
     * @param  Closure(): WalletTransaction  $callback
     */
    public static function run(string $key, Closure $callback, int $ttlSeconds = 60): WalletTransaction
    {
        $existing = WalletTransaction::query()
            ->where('idempotency_key', $key)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $lock = Cache::lock("idempotency:{$key}", $ttlSeconds);

        return $lock->block($ttlSeconds, function () use ($key, $callback) {
            $existing = WalletTransaction::query()
                ->where('idempotency_key', $key)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            return $callback();
        });
    }
}
