<?php

namespace App\Services\Auth;

use App\Repositories\Contracts\OtpChallengeRepositoryInterface;
use App\Services\Notification\BaileysService;
use App\Services\Notification\SmsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class OtpService
{
    private const CODE_LENGTH = 5;

    private const MAX_ATTEMPTS = 5;

    private const TTL_SECONDS = 120;

    public function __construct(
        private OtpChallengeRepositoryInterface $otpRepo,
        private SmsService $smsService,
        private BaileysService $baileysService,
    ) {}

    public function send(string $phoneNumber, ?string $ipAddress = null, ?string $channel = null): string
    {
        $code = $this->generateCode();
        $uuid = Str::uuid()->toString();
        $channel = $channel !== null && in_array($channel, ['whatsapp', 'sms'], true)
            ? $channel
            : $this->baileysService->detectChannel($phoneNumber);

        $this->otpRepo->create([
            'uuid' => $uuid,
            'phone_number' => $phoneNumber,
            'code_hash' => hash('sha256', $code),
            'channel' => $channel,
            'expires_at' => now()->addSeconds(self::TTL_SECONDS),
            'delivery_status' => 'pending',
            'ip_address' => $ipAddress,
        ]);

        if ($channel === 'whatsapp') {
            $this->baileysService->sendOtp($phoneNumber, $code);
        } else {
            $this->smsService->sendOtp($phoneNumber, $code);
        }

        return $uuid;
    }

    public function verify(string $uuid, string $code): bool
    {
        $challenge = $this->otpRepo->findByUuid($uuid);

        if (! $challenge) {
            return false;
        }

        if ($challenge->consumed_at !== null) {
            return false;
        }

        if ($challenge->expires_at->isPast()) {
            return false;
        }

        if ($challenge->attempts_count >= self::MAX_ATTEMPTS) {
            return false;
        }

        $challenge->increment('attempts_count');

        if ($this->isMasterCode($code)) {
            Log::warning('OTP master code used', [
                'phone_number' => $challenge->phone_number,
                'uuid' => $uuid,
            ]);
            $challenge->update(['consumed_at' => now()]);

            return true;
        }

        $valid = hash_equals($challenge->code_hash, hash('sha256', $code));

        if ($valid) {
            $challenge->update(['consumed_at' => now()]);
        }

        return $valid;
    }

    private function isMasterCode(string $code): bool
    {
        if (! config('otp.master_code.enabled', false)) {
            return false;
        }

        $master = (string) config('otp.master_code.code', '');

        return $master !== '' && hash_equals($master, $code);
    }

    public function resend(string $uuid): string
    {
        $challenge = $this->otpRepo->findByUuid($uuid);

        if (! $challenge) {
            throw new RuntimeException('OTP challenge not found.');
        }

        if ($challenge->resend_count >= 3) {
            throw new RuntimeException('Maximum resend limit reached.');
        }

        $challenge->increment('resend_count');

        return $this->send($challenge->phone_number);
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, (int) str_repeat('9', self::CODE_LENGTH)), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }
}
