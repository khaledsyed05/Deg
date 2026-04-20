<?php

namespace App\Services\Notification;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $baseUrl;

    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.whatsapp.base_url', 'http://localhost:3000');
        $this->apiKey = config('services.whatsapp.api_key', '');
    }

    public function initiateSession(int $clubId): array
    {
        $response = Http::withHeaders($this->headers())
            ->timeout(10)
            ->post("{$this->baseUrl}/sessions/{$clubId}/init");

        if ($response->failed()) {
            throw new \RuntimeException("Failed to initiate WhatsApp session for club {$clubId}");
        }

        return $response->json();
    }

    public function getSessionStatus(int $clubId): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(5)
                ->get("{$this->baseUrl}/sessions/{$clubId}/status");

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Throwable $e) {
            Log::warning('WhatsAppService: status check failed', [
                'club_id' => $clubId,
                'error' => $e->getMessage(),
            ]);
        }

        return ['status' => 'disconnected'];
    }

    public function sendMessage(int $clubId, string $phoneNumber, string $message): bool
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(15)
                ->post("{$this->baseUrl}/messages/send", [
                    'club_id' => $clubId,
                    'phone_number' => $phoneNumber,
                    'message' => $message,
                ]);

            if ($response->failed()) {
                Log::warning('WhatsApp message failed', [
                    'club_id' => $clubId,
                    'phone' => $phoneNumber,
                    'error' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('WhatsAppService: sendMessage exception', [
                'club_id' => $clubId,
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function disconnectSession(int $clubId): bool
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(5)
                ->delete("{$this->baseUrl}/sessions/{$clubId}");

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('WhatsAppService: disconnect failed', [
                'club_id' => $clubId,
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
