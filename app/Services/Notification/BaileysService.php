<?php

namespace App\Services\Notification;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Platform-level WhatsApp (used for OTP + system messages).
 * Talks to the Baileys Node bridge via the shared /platform endpoints.
 */
class BaileysService
{
    public function __construct(
        private string $serviceUrl,
        private string $apiKey,
    ) {}

    /**
     * @return array{status: string, qr_code?: string}
     */
    public function initiateSession(): array
    {
        $response = Http::withHeaders($this->headers())
            ->timeout(10)
            ->post("{$this->serviceUrl}/platform/init");

        if ($response->failed()) {
            throw new \RuntimeException('Failed to initiate platform WhatsApp session');
        }

        return $response->json();
    }

    /**
     * @return array{status: string, qr_code?: string}
     */
    public function getSessionStatus(): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(5)
                ->get("{$this->serviceUrl}/platform/status");

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Throwable $e) {
            Log::warning('BaileysService: status check failed', ['error' => $e->getMessage()]);
        }

        return ['status' => 'disconnected'];
    }

    public function disconnectSession(): bool
    {
        try {
            return Http::withHeaders($this->headers())
                ->timeout(5)
                ->delete("{$this->serviceUrl}/platform")
                ->successful();
        } catch (\Throwable $e) {
            Log::warning('BaileysService: disconnect failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Detect whether a phone number has WhatsApp.
     * Returns 'whatsapp' or 'sms'.
     */
    public function detectChannel(string $phoneNumber): string
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(5)
                ->get("{$this->serviceUrl}/platform/check/".rawurlencode($phoneNumber));

            if ($response->successful() && $response->json('has_whatsapp')) {
                return 'whatsapp';
            }
        } catch (\Throwable $e) {
            Log::warning('BaileysService: channel detection failed, falling back to SMS', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);
        }

        return 'sms';
    }

    public function sendOtp(string $phoneNumber, string $code): bool
    {
        return $this->send($phoneNumber, "رمز التحقق الخاص بك هو: *{$code}*\nصالح لمدة دقيقتين.");
    }

    public function send(string $phoneNumber, string $message): bool
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(10)
                ->post("{$this->serviceUrl}/platform/send", [
                    'phone_number' => $phoneNumber,
                    'message' => $message,
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('BaileysService: send failed', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /** @return array<string, string> */
    private function headers(): array
    {
        return $this->apiKey ? ['X-Api-Key' => $this->apiKey] : [];
    }
}
