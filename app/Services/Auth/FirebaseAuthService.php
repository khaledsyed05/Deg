<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Validates Firebase ID tokens via Google JWKS.
 * Fetches public keys and verifies RSA signature + claims.
 */
class FirebaseAuthService
{
    private const JWKS_URL = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com';

    private const JWKS_CACHE_TTL = 3600;

    public function __construct(
        private string $projectId,
    ) {}

    /**
     * @return array{uid: string, email: ?string, phone: ?string, name: ?string, picture: ?string}
     *
     * @throws RuntimeException when token is invalid or expired
     */
    public function verifyIdToken(string $idToken): array
    {
        $parts = explode('.', $idToken);

        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid Firebase ID token format.');
        }

        $header = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

        if (! $header || ! $payload) {
            throw new RuntimeException('Failed to decode Firebase token.');
        }

        // Validate claims
        if (($payload['aud'] ?? '') !== $this->projectId) {
            throw new RuntimeException('Firebase token audience mismatch.');
        }

        if (($payload['iss'] ?? '') !== "https://securetoken.google.com/{$this->projectId}") {
            throw new RuntimeException('Firebase token issuer mismatch.');
        }

        if (($payload['exp'] ?? 0) < time()) {
            throw new RuntimeException('Firebase token has expired.');
        }

        // Verify signature against JWKS
        $certs = $this->fetchPublicKeys();
        $kid = $header['kid'] ?? null;

        if (! $kid || ! isset($certs[$kid])) {
            throw new RuntimeException('Firebase token key ID not found in JWKS.');
        }

        $verified = openssl_verify(
            "{$parts[0]}.{$parts[1]}",
            base64_decode(strtr($parts[2], '-_', '+/')),
            openssl_x509_read($certs[$kid]),
            OPENSSL_ALGO_SHA256
        );

        if ($verified !== 1) {
            throw new RuntimeException('Firebase token signature verification failed.');
        }

        return [
            'uid' => $payload['sub'],
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone_number'] ?? null,
            'name' => $payload['name'] ?? null,
            'picture' => $payload['picture'] ?? null,
        ];
    }

    /** @return array<string, string> */
    private function fetchPublicKeys(): array
    {
        return Cache::remember('firebase_jwks', self::JWKS_CACHE_TTL, function () {
            $response = Http::timeout(5)->get(self::JWKS_URL);

            if (! $response->successful()) {
                throw new RuntimeException('Failed to fetch Firebase public keys.');
            }

            return $response->json();
        });
    }
}
