<?php

namespace App\Services\Notification;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Firebase Cloud Messaging — HTTP v1 API.
 * Tokens are refreshed via Google OAuth2 service account.
 */
class FcmService
{
    public function __construct(
        private string $projectId,
        private string $serviceAccountJson,
    ) {}

    public function sendToToken(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        $accessToken = $this->fetchAccessToken();

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
        $credentials = json_decode($this->serviceAccountJson, true);

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
