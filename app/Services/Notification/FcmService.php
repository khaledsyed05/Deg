<?php

namespace App\Services\Notification;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Firebase Cloud Messaging — HTTP v1 API.
 * Tokens are refreshed via Google OAuth2 service account.
 *
 * Credentials are resolved lazily: a file at credentialsPath wins, with the
 * raw JSON env value as a fallback. The parsed credentials are cached for
 * the lifetime of the instance.
 */
class FcmService
{
    /** @var array<string, mixed>|null */
    private ?array $credentials = null;

    public function __construct(
        private string $projectId,
        private string $credentialsPath,
        private string $serviceAccountJson,
    ) {}

    public function sendToToken(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        try {
            $accessToken = $this->fetchAccessToken();
        } catch (RuntimeException $e) {
            Log::warning('FCM credentials unavailable', ['error' => $e->getMessage()]);

            return false;
        }

        $response = Http::withToken($accessToken)
            ->timeout(10)
            ->post("https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send", [
                'message' => [
                    'token' => $fcmToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => array_map('strval', $data),
                ],
            ]);

        if (! $response->successful()) {
            Log::warning('FCM send failed', [
                'token' => substr($fcmToken, 0, 20).'...',
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    private function fetchAccessToken(): string
    {
        $credentials = $this->resolveCredentials();

        $now = time();
        $jwt = $this->buildJwt($credentials, $now);

        $response = Http::asForm()
            ->timeout(10)
            ->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

        return $response->json('access_token');
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveCredentials(): array
    {
        if ($this->credentials !== null) {
            return $this->credentials;
        }

        if ($this->projectId === '') {
            throw new RuntimeException('FIREBASE_PROJECT_ID is not set.');
        }

        $json = null;

        if ($this->credentialsPath !== '' && is_file($this->credentialsPath) && is_readable($this->credentialsPath)) {
            $json = file_get_contents($this->credentialsPath) ?: null;
        }

        if ($json === null && $this->serviceAccountJson !== '' && $this->serviceAccountJson !== '{}') {
            $json = $this->serviceAccountJson;
        }

        if ($json === null) {
            throw new RuntimeException('Firebase service account is not configured. Provide FIREBASE_CREDENTIALS file or FIREBASE_SERVICE_ACCOUNT JSON.');
        }

        $decoded = json_decode($json, true);

        if (! is_array($decoded) || empty($decoded['client_email']) || empty($decoded['private_key'])) {
            throw new RuntimeException('Firebase service account JSON is invalid: missing client_email or private_key.');
        }

        return $this->credentials = $decoded;
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function buildJwt(array $credentials, int $now): string
    {
        $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $signingInput = "{$header}.{$payload}";
        openssl_sign($signingInput, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);

        return "{$signingInput}.".base64_encode($signature);
    }
}
