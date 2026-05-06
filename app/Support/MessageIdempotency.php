<?php

namespace App\Support;

use App\Models\Message;
use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Idempotency primitive for chat messages.
 *
 * Mirrors the wallet `Idempotency` helper's lock + key pattern but
 * targets the `Message` model — refactoring the existing wallet
 * helper to be generic is out of Sprint 7 scope.
 *
 * Two safeguards combined:
 *   1. Short-lived `Cache::lock` blocks concurrent attempts in the
 *      same process / cluster window.
 *   2. The unique index on `messages.idempotency_key` is the durable
 *      backstop — even if the cache lock is evicted, a duplicate
 *      insert fails fast.
 *
 * If a message already exists with this key, the previous row is
 * returned WITHOUT re-running the callback. This makes /messages
 * safe to retry on flaky networks.
 */
class MessageIdempotency
{
    /**
     * @param  Closure(): Message  $callback
     */
    public static function run(string $key, Closure $callback, int $ttlSeconds = 60): Message
    {
        $existing = Message::query()->where('idempotency_key', $key)->first();

        if ($existing !== null) {
            return $existing;
        }

        $lock = Cache::lock("idempotency:msg:{$key}", $ttlSeconds);

        return $lock->block($ttlSeconds, function () use ($key, $callback) {
            $existing = Message::query()->where('idempotency_key', $key)->first();

            if ($existing !== null) {
                return $existing;
            }

            return $callback();
        });
    }
}
