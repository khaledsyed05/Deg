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

class FatoraGateway implements PaymentGatewayInterface
{
    private string $baseUrl;

    private string $apiKey;

    private string $webhookSecret;

    public function __construct(
        private PaymentRepositoryInterface $paymentRepo,
    ) {
        $this->baseUrl = config('payments.fatora.base_url', '');
        $this->apiKey = config('payments.fatora.api_key', '');
        $this->webhookSecret = config('payments.fatora.webhook_secret', '');
    }

    public function initiate(Booking $booking, string $phoneNumber = ''): PaymentInitiationResult
    {
        $payment = $this->paymentRepo->create([
            'booking_id'   => $booking->id,
            'user_id'      => $booking->user_id,
            'amount'       => $booking->deposit_amount ?: $booking->total_price,
            'currency'     => $booking->currency,
            'provider'     => PaymentProvider::Fatora,
            'flow_type'    => PaymentFlowType::Webview,
            'status'       => 'pending',
            'initiated_at' => now(),
        ]);

        $redirectUrl = null;

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type'  => 'application/json',
                ])
                ->post("{$this->baseUrl}/v2/CreateInvoice", [
                    'amount'          => $payment->amount,
                    'currency'        => $payment->currency,
                    'customer_phone'  => $phoneNumber ?: null,
                    'order_id'        => $booking->booking_code,
                    'description'     => "حجز رقم {$booking->booking_code}",
                    'webhook_url'     => route('webhooks.fatora.callback'),
                    'success_url'     => config('app.url'),
                    'cancel_url'      => config('app.url'),
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $redirectUrl = $data['InvoiceURL'] ?? null;
                $this->paymentRepo->update($payment, [
                    'provider_transaction_id' => $data['InvoiceId'] ?? null,
                    'provider_payload'        => $data,
                ]);
            } else {
                Log::warning('Fatora initiation API error', [
                    'booking_id' => $booking->id,
                    'status'     => $response->status(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Fatora initiation request failed', [
                'booking_id' => $booking->id,
                'error'      => $e->getMessage(),
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
            'status'         => $this->mapStatus($payload['status'] ?? ''),
            'transaction_id' => $payload['InvoiceId'] ?? '',
            'amount'         => (float) ($payload['amount'] ?? 0),
            'reference'      => $payload['order_id'] ?? null,
        ];
    }

    public function checkStatus(string $transactionId): array
    {
        $response = Http::timeout(15)
            ->withHeaders(['Authorization' => "Bearer {$this->apiKey}"])
            ->get("{$this->baseUrl}/v2/GetInvoiceStatus/{$transactionId}");

        if ($response->failed()) {
            throw new \RuntimeException('Failed to check Fatora payment status');
        }

        $data = $response->json();

        return [
            'status'         => $this->mapStatus($data['InvoiceStatus'] ?? ''),
            'transaction_id' => $data['InvoiceId'] ?? $transactionId,
            'amount'         => (float) ($data['InvoiceValue'] ?? 0),
        ];
    }

    public function getName(): string
    {
        return 'fatora';
    }

    private function mapStatus(string $gatewayStatus): string
    {
        return match (strtolower($gatewayStatus)) {
            'paid', 'success'    => 'completed',
            'expired', 'failed'  => 'failed',
            'cancelled'          => 'cancelled',
            default              => 'pending',
        };
    }
}
