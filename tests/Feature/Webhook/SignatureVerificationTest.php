<?php

namespace Tests\Feature\Webhook;

use App\Support\WebhookSignature;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Sprint 8 B3 — verifies that VerifyWebhookSignature middleware
 * actually rejects unsigned / tampered webhook bodies when
 * verify_signatures is on.
 *
 * Uses the Syriatel webhook route as the canonical test vehicle;
 * the middleware is provider-agnostic (passes :provider as a
 * parameter), so MTN behaves identically.
 */
class SignatureVerificationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.syriatel.webhook_secret' => 'test-syriatel-secret',
            'services.syriatel.webhook_header' => 'X-Signature',
            'services.syriatel.verify_signatures' => true,
            'services.mtn.webhook_secret' => 'test-mtn-secret',
            'services.mtn.webhook_header' => 'X-Signature',
            'services.mtn.verify_signatures' => true,
        ]);

        // Register a test-only route that exercises ONLY the
        // middleware, isolating it from the production webhook
        // controllers (which have their own gateway-level
        // signature checks). The middleware test should answer
        // "does VerifyWebhookSignature do its job?", not "does
        // the SyriatelWebhookController accept this body?"
        Route::post(
            'test/webhooks/syriatel',
            fn () => response()->json(['ok' => true]),
        )->middleware('verify.webhook:syriatel');
    }

    public function test_valid_signature_passes_through(): void
    {
        $payload = ['external_id' => 'abc-123', 'status' => 'paid'];
        $body = json_encode($payload);
        $signature = WebhookSignature::sign($body, 'test-syriatel-secret');

        $response = $this->call(
            'POST',
            '/test/webhooks/syriatel',
            [],
            [],
            [],
            ['HTTP_X-Signature' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $body,
        );

        $this->assertNotSame(401, $response->status(), 'Valid signature should NOT be rejected');
    }

    public function test_invalid_signature_returns_401(): void
    {
        $body = '{"external_id":"abc","status":"paid"}';

        $response = $this->call(
            'POST',
            '/test/webhooks/syriatel',
            [],
            [],
            [],
            ['HTTP_X-Signature' => 'definitely-not-the-real-signature', 'CONTENT_TYPE' => 'application/json'],
            $body,
        );

        $response->assertStatus(401);
        $this->assertSame('Invalid webhook signature.', $response->json('message'));
    }

    public function test_missing_signature_header_returns_401(): void
    {
        $body = '{"external_id":"abc"}';

        $response = $this->call(
            'POST',
            '/test/webhooks/syriatel',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $body,
        );

        $response->assertStatus(401);
    }

    public function test_tampered_body_with_old_signature_returns_401(): void
    {
        $original = '{"amount":100}';
        $signature = WebhookSignature::sign($original, 'test-syriatel-secret');
        $tampered = '{"amount":1000000}';

        $response = $this->call(
            'POST',
            '/test/webhooks/syriatel',
            [],
            [],
            [],
            ['HTTP_X-Signature' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $tampered,
        );

        $response->assertStatus(401);
    }

    public function test_verify_signatures_false_lets_unsigned_payload_through(): void
    {
        config(['services.syriatel.verify_signatures' => false]);

        $response = $this->call(
            'POST',
            '/test/webhooks/syriatel',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"unsigned":"payload"}',
        );

        $this->assertNotSame(401, $response->status(), 'verify_signatures=false should bypass the check');
    }

    public function test_missing_secret_in_config_returns_401(): void
    {
        config(['services.syriatel.webhook_secret' => '']);

        $response = $this->call(
            'POST',
            '/test/webhooks/syriatel',
            [],
            [],
            [],
            ['HTTP_X-Signature' => 'whatever', 'CONTENT_TYPE' => 'application/json'],
            '{}',
        );

        $response->assertStatus(401);
        $this->assertStringContainsString('not configured', $response->json('message'));
    }
}
