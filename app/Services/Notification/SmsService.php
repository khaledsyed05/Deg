<?php

namespace App\Services\Notification;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SMS delivery via configured provider (Syriatel / MTN bulk SMS API).
 */
class SmsService
{
    public function __construct(
        private string $apiUrl,
        private string $apiKey,
        private string $senderId,
    ) {}

    public function sendOtp(string $phoneNumber, string $code): bool
    {
        return $this->send($phoneNumber, "Your verification code is: {$code}");
    }

    public function send(string $phoneNumber, string $message): bool
    {
        if ($this->apiUrl === '') {
            Log::warning('SmsService: skipping send — apiUrl not configured', [
                'phone' => $phoneNumber,
            ]);

            return false;
        }

        try {
            // TODO: wire to actual SMS provider API
            $response = Http::withHeaders(['Authorization' => "Bearer {$this->apiKey}"])
                ->timeout(10)
                ->post($this->apiUrl, [
                    'to' => $phoneNumber,
                    'from' => $this->senderId,
                    'message' => $message,
                ]);
        } catch (\Throwable $e) {
            Log::warning('SmsService: send threw, suppressing', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        if (! $response->successful()) {
            Log::warning('SMS send failed', [
                'phone' => $phoneNumber,
                'status' => $response->status(),
            ]);

            return false;
        }

        return true;
    }
}
