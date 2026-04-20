<?php

namespace App\Services\Notification;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp messaging via Baileys Node.js bridge service.
 * Also handles channel detection (WhatsApp vs SMS).
 */
class BaileysService
{
    public function __construct(
        private string $serviceUrl,
        private string $apiKey,
    ) {}

    /**
     * Detect whether a phone number has WhatsApp.
     * Returns 'whatsapp' or 'sms'.
     */
    public function detectChannel(string $phoneNumber): string
    {
        try {
            $response = Http::withHeaders(['X-Api-Key' => $this->apiKey])
                ->timeout(5)
                ->get("{$this->serviceUrl}/check/{$phoneNumber}");

            if ($response->successful() && $response->json('has_whatsapp')) {
                return 'whatsapp';
            }
        } catch (\Throwable $e) {
            Log::warning('Baileys channel detection failed, falling back to SMS.', [
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
            $response = Http::withHeaders(['X-Api-Key' => $this->apiKey])
                ->timeout(10)
                ->post("{$this->serviceUrl}/send", [
                    'to' => $phoneNumber,
                    'message' => $message,
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Baileys send failed.', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
