<?php

namespace App\Support;

/**
 * Constant-time HMAC-SHA256 signature verification for incoming
 * webhook bodies.
 *
 * NEVER use `==` or `===` for HMAC comparison — that's a timing
 * attack. `hash_equals()` is the correct primitive: it compares
 * in constant time regardless of where the strings differ.
 */
class WebhookSignature
{
    public static function verify(string $payload, string $providedSignature, string $secret): bool
    {
        if ($providedSignature === '' || $secret === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $providedSignature);
    }

    /**
     * Helper for tests / clients: produce a signature for a payload.
     */
    public static function sign(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }
}
