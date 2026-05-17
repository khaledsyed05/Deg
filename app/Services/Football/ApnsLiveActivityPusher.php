<?php

namespace App\Services\Football;

use App\Models\Football\MatchActivityToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Pushes Live Activity updates to Apple Push Notification service (APNs)
 * for iOS clients that opted in to a specific match.
 *
 * The service is gated by services.apns.enabled — when disabled (or creds
 * missing) every call is a structured no-op so the polling pipeline keeps
 * running. When enabled, it signs a fresh ES256 JWT (cached 50 minutes)
 * and pushes a `liveactivity` notification per token.
 */
class ApnsLiveActivityPusher
{
    private const PRODUCTION_HOST = 'https://api.push.apple.com';

    private const SANDBOX_HOST = 'https://api.sandbox.push.apple.com';

    private const JWT_CACHE_KEY = 'apns:live_activity_jwt';

    private const JWT_TTL_SECONDS = 3000;

    public function __construct(
        private readonly bool $enabled,
        private readonly bool $useSandbox,
        private readonly ?string $authKeyPath,
        private readonly ?string $keyId,
        private readonly ?string $teamId,
        private readonly string $bundleId,
    ) {}

    /**
     * @param  array{home_score:int,away_score:int,status_short:string,minute:int}  $contentState
     * @return int Number of successful pushes.
     */
    public function pushUpdate(string $fixtureExternalId, array $contentState, bool $isFinal = false): int
    {
        if (! $this->isOperational()) {
            return 0;
        }

        $tokens = MatchActivityToken::query()
            ->forFixture($fixtureExternalId)
            ->active()
            ->ios()
            ->get();

        if ($tokens->isEmpty()) {
            return 0;
        }

        $jwt = $this->resolveJwt();
        if ($jwt === null) {
            return 0;
        }

        $payload = [
            'aps' => [
                'timestamp' => time(),
                'event' => $isFinal ? 'end' : 'update',
                'content-state' => $contentState,
            ],
        ];

        $success = 0;
        foreach ($tokens as $token) {
            if ($this->sendOne($jwt, $token, $payload)) {
                $success++;
            }
        }

        return $success;
    }

    public function isOperational(): bool
    {
        return $this->enabled
            && $this->authKeyPath !== null && $this->authKeyPath !== ''
            && $this->keyId !== null && $this->keyId !== ''
            && $this->teamId !== null && $this->teamId !== ''
            && is_file($this->authKeyPath)
            && is_readable($this->authKeyPath);
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    private function sendOne(string $jwt, MatchActivityToken $token, array $payload): bool
    {
        $host = $this->useSandbox ? self::SANDBOX_HOST : self::PRODUCTION_HOST;
        $url = $host.'/3/device/'.$token->push_token;

        $response = Http::withHeaders([
            'authorization' => 'bearer '.$jwt,
            'apns-topic' => $this->bundleId.'.push-type.liveactivity',
            'apns-push-type' => 'liveactivity',
            'apns-priority' => '10',
            'apns-expiration' => (string) (time() + 3600),
        ])
            ->timeout(5)
            ->withBody(json_encode($payload), 'application/json')
            ->post($url);

        if ($response->successful()) {
            return true;
        }

        $reason = $response->json('reason') ?? $response->body();

        Log::channel('football')->warning('APNs Live Activity send failed', [
            'fixture_external_id' => $token->fixture_external_id,
            'status' => $response->status(),
            'reason' => $reason,
        ]);

        if (in_array($reason, ['BadDeviceToken', 'Unregistered', 'ExpiredToken'], true)) {
            $token->update(['is_active' => false]);
        }

        return false;
    }

    private function resolveJwt(): ?string
    {
        return Cache::remember(self::JWT_CACHE_KEY, self::JWT_TTL_SECONDS, function (): ?string {
            try {
                return $this->signJwt();
            } catch (\Throwable $e) {
                Log::channel('football')->error('APNs JWT signing failed', ['error' => $e->getMessage()]);

                return null;
            }
        });
    }

    private function signJwt(): string
    {
        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'ES256',
            'typ' => 'JWT',
            'kid' => $this->keyId,
        ], JSON_UNESCAPED_SLASHES));

        $payload = $this->base64UrlEncode(json_encode([
            'iss' => $this->teamId,
            'iat' => time(),
        ], JSON_UNESCAPED_SLASHES));

        $signingInput = $header.'.'.$payload;

        $privateKey = openssl_pkey_get_private('file://'.$this->authKeyPath);
        if ($privateKey === false) {
            throw new RuntimeException('Unable to load APNs auth key from '.$this->authKeyPath);
        }

        if (! openssl_sign($signingInput, $derSignature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('openssl_sign failed for APNs JWT');
        }

        $rawSignature = $this->derToRawSignature($derSignature);

        return $signingInput.'.'.$this->base64UrlEncode($rawSignature);
    }

    /**
     * Convert a DER-encoded ECDSA signature into the 64-byte R||S form
     * required by JOSE/JWT ES256.
     */
    private function derToRawSignature(string $der): string
    {
        $bytes = array_values(unpack('C*', $der));
        $offset = 0;

        if ($bytes[$offset++] !== 0x30) {
            throw new RuntimeException('Malformed DER signature: missing SEQUENCE tag');
        }

        $sequenceLength = $bytes[$offset++];
        if ($sequenceLength > 0x80) {
            $offset += $sequenceLength - 0x80;
        }

        $r = $this->readDerInteger($bytes, $offset);
        $s = $this->readDerInteger($bytes, $offset);

        return $this->padTo32($r).$this->padTo32($s);
    }

    /**
     * @param  array<int,int>  $bytes
     */
    private function readDerInteger(array $bytes, int &$offset): string
    {
        if ($bytes[$offset++] !== 0x02) {
            throw new RuntimeException('Malformed DER signature: missing INTEGER tag');
        }

        $length = $bytes[$offset++];
        $value = '';
        for ($i = 0; $i < $length; $i++) {
            $value .= chr($bytes[$offset++]);
        }

        return ltrim($value, "\x00");
    }

    private function padTo32(string $value): string
    {
        return str_pad($value, 32, "\x00", STR_PAD_LEFT);
    }

    private function base64UrlEncode(string $input): string
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }
}
