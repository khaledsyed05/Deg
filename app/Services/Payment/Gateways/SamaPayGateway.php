<?php

namespace App\Services\Payment\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\Payment\PaymentInitiationResult;
use App\Enums\PaymentFlowType;
use App\Enums\PaymentProvider;
use App\Models\Booking;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SamaPayGateway implements PaymentGatewayInterface
{
    private string $baseUrl;

    private string $apiKey;

    private string $webhookSecret;

    public function __construct(
        private PaymentRepositoryInterface $paymentRepo,
    ) {
        $this->baseUrl = config('payments.samapay.base_url', '');
        $this->apiKey = config('payments.samapay.api_key', '');
        $this->webhookSecret = config('payments.samapay.webhook_secret', '');
    }

    public function initiate(Booking $booking, string $phoneNumber = ''): PaymentInitiationResult
    {
        $payment = $this->paymentRepo->create([
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'amount' => $booking->deposit_amount ?: $booking->total_price,
            'currency' => $booking->currency,
            'provider' => PaymentProvider::SamaPay,
            'flow_type' => PaymentFlowType::Webview,
            'status' => 'pending',
            'initiated_at' => now(),
        ]);

        $redirectUrl = null;

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/api/v1/payments/create", [
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'order_id' => $booking->booking_code,
                    'description' => "حجز رقم {$booking->booking_code}",
                    'webhook_url' => route('webhooks.samapay.callback'),
                    'return_url' => config('app.url'),
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $redirectUrl = $data['payment_url'] ?? null;
                $this->paymentRepo->update($payment, [
                    'provider_transaction_id' => $data['payment_id'] ?? null,
                    'provider_payload' => $data,
                ]);
            } else {
                Log::warning('SamaPay initiation API error', [
                    'booking_id' => $booking->id,
                    'status' => $response->status(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('SamaPay initiation request failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }

        return new PaymentInitiationResult(
            payment: $payment->fresh(),
            flowType: PaymentFlowType::Webview,
            redirectUrl: $redirectUrl,
            otpChallengeUuid: null,
        );
    }

    public function verifyWebhookSignature(array $payload, string $signature): bool
    {
        if (empty($this->webhookSecret)) {
            return false;
        }

        $computed = hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE), $this->webhookSecret);

        return hash_equals($computed, $signature);
    }

    public function processCallback(array $payload): array
    {
        return [
            'status' => $this->mapStatus($payload['status'] ?? ''),
            'transaction_id' => $payload['payment_id'] ?? '',
            'amount' => (float) ($payload['amount'] ?? 0),
            'reference' => $payload['order_id'] ?? null,
        ];
    }

    public function checkStatus(string $transactionId): array
    {
        $response = Http::timeout(15)
            ->withHeaders(['Authorization' => "Bearer {$this->apiKey}"])
            ->get("{$this->baseUrl}/api/v1/payments/{$transactionId}/status");

        if ($response->failed()) {
            throw new \RuntimeException('Failed to check SamaPay payment status');
        }

        $data = $response->json();

        return [
            'status' => $this->mapStatus($data['status'] ?? ''),
            'transaction_id' => $data['payment_id'] ?? $transactionId,
            'amount' => (float) ($data['amount'] ?? 0),
        ];
    }

    public function getName(): string
    {
        return 'sama_pay';
    }

    private function mapStatus(string $gatewayStatus): string
    {
        return match (strtolower($gatewayStatus)) {
            'paid', 'success', 'completed' => 'completed',
            'processing' => 'processing',
            'failed', 'declined' => 'failed',
            'cancelled', 'canceled' => 'cancelled',
            default => 'pending',
        };
    }
}
