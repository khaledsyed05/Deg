<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use RuntimeException;

/**
 * RFC 6238 TOTP (Time-based One-Time Password) for 2FA.
 * Uses 30-second time steps, 6-digit codes, HMAC-SHA1.
 */
class TotpService
{
    private const STEP = 30;

    private const DIGITS = 6;

    private const WINDOW = 1; // accept 1 step before/after for clock skew

    public function __construct(
        private UserRepositoryInterface $userRepo,
    ) {}

    public function generateSecret(): string
    {
        $bytes = random_bytes(20);

        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    public function enable(User $user, string $secret, string $code): void
    {
        if (! $this->verify($secret, $code)) {
            throw new RuntimeException('Invalid TOTP code. Please try again.');
        }

        $this->userRepo->update($user, [
            'google2fa_secret' => $secret,
            'google2fa_enabled_at' => now(),
        ]);
    }

    public function disable(User $user): void
    {
        $this->userRepo->update($user, [
            'google2fa_secret' => null,
            'google2fa_enabled_at' => null,
        ]);
    }

    public function verify(string $secret, string $code): bool
    {
        $timestamp = (int) floor(time() / self::STEP);

        for ($i = -self::WINDOW; $i <= self::WINDOW; $i++) {
            if ($this->hotp($secret, $timestamp + $i) === $code) {
                return true;
            }
        }

        return false;
    }

    private function hotp(string $secret, int $counter): string
    {
        $key = base64_decode(strtr($secret, '-_', '+/'));
        $msg = pack('J', $counter);
        $hash = hash_hmac('sha1', $msg, $key, true);
        $offset = ord($hash[19]) & 0xF;
        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF)
        ) % (10 ** self::DIGITS);

        return str_pad((string) $code, self::DIGITS, '0', STR_PAD_LEFT);
    }
}
