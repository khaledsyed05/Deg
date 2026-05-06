<?php

namespace Tests\Unit;

use App\Support\WebhookSignature;
use PHPUnit\Framework\TestCase;

class WebhookSignatureTest extends TestCase
{
    public function test_matching_signature_returns_true(): void
    {
        $payload = '{"foo":"bar"}';
        $secret = 'shhh';
        $signature = WebhookSignature::sign($payload, $secret);

        $this->assertTrue(WebhookSignature::verify($payload, $signature, $secret));
    }

    public function test_mismatched_signature_returns_false(): void
    {
        $payload = '{"foo":"bar"}';
        $secret = 'shhh';

        $this->assertFalse(WebhookSignature::verify($payload, 'definitely-wrong', $secret));
    }

    public function test_empty_signature_returns_false(): void
    {
        $this->assertFalse(WebhookSignature::verify('{"foo":"bar"}', '', 'secret'));
    }

    public function test_empty_secret_returns_false(): void
    {
        $sig = WebhookSignature::sign('{"foo":"bar"}', 'secret');
        $this->assertFalse(WebhookSignature::verify('{"foo":"bar"}', $sig, ''));
    }

    public function test_tampered_payload_fails_verification(): void
    {
        $original = '{"amount":100}';
        $secret = 'shhh';
        $signature = WebhookSignature::sign($original, $secret);

        $tampered = '{"amount":1000000}';
        $this->assertFalse(WebhookSignature::verify($tampered, $signature, $secret));
    }

    public function test_uses_constant_time_comparison(): void
    {
        // hash_equals() short-circuits ONLY on length mismatch.
        // Equal-length strings are always compared byte-by-byte.
        // We can't test timing directly in unit tests, but we can
        // verify the function under the hood is hash_equals (if a
        // future refactor swaps it for == that's an instant red).
        $reflection = new \ReflectionMethod(WebhookSignature::class, 'verify');
        $body = file_get_contents($reflection->getFileName());
        $this->assertStringContainsString('hash_equals', $body);
        $this->assertStringNotContainsString('strcmp', $body);
    }
}
