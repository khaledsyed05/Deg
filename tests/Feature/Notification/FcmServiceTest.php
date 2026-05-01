<?php

namespace Tests\Feature\Notification;

use App\Services\Notification\FcmService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FcmServiceTest extends TestCase
{
    private static ?string $privateKey = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($resource, $privateKey);
        self::$privateKey = $privateKey;
    }

    public function test_sends_payload_to_fcm_endpoint_with_bearer_token(): void
    {
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'fake-access-token'], 200),
            'fcm.googleapis.com/*' => Http::response(['name' => 'projects/p/messages/1'], 200),
        ]);

        $service = $this->makeService();

        $ok = $service->sendToToken('device-token-xyz', 'Hello', 'World', ['type' => 'unit_test']);

        $this->assertTrue($ok);

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'fcm.googleapis.com')) {
                return false;
            }
            $body = $request->data();

            return $request->hasHeader('Authorization', 'Bearer fake-access-token')
                && str_contains($request->url(), 'projects/test-project/messages:send')
                && ($body['message']['token'] ?? null) === 'device-token-xyz'
                && ($body['message']['notification']['title'] ?? null) === 'Hello'
                && ($body['message']['notification']['body'] ?? null) === 'World'
                && ($body['message']['data']['type'] ?? null) === 'unit_test';
        });
    }

    public function test_returns_false_on_fcm_error_response(): void
    {
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'tok'], 200),
            'fcm.googleapis.com/*' => Http::response(['error' => 'INVALID_ARGUMENT'], 400),
        ]);

        $service = $this->makeService();

        $this->assertFalse($service->sendToToken('bad-token', 't', 'b'));
    }

    public function test_returns_false_when_credentials_are_missing(): void
    {
        Http::fake();

        $service = new FcmService(
            projectId: 'test-project',
            credentialsPath: '/nonexistent/path.json',
            serviceAccountJson: '',
        );

        $this->assertFalse($service->sendToToken('any-token', 't', 'b'));
    }

    private function makeService(): FcmService
    {
        $serviceAccount = json_encode([
            'client_email' => 'fake@test-project.iam.gserviceaccount.com',
            'private_key' => self::$privateKey,
        ]);

        return new FcmService(
            projectId: 'test-project',
            credentialsPath: '',
            serviceAccountJson: $serviceAccount,
        );
    }
}
