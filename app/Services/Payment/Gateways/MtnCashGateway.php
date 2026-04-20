<?php

namespace App\Services\Payment\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\Payment\PaymentInitiationResult;
use App\Enums\PaymentFlowType;
use App\Enums\PaymentProvider;
use App\Models\Booking;
use App\Models\Payment;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MtnCashGateway implements PaymentGatewayInterface
{
    private string $baseUrl;

    private string $apiKey;

    private string $merchantId;

    private string $webhookSecret;

    public function __construct(
        private PaymentRepositoryInterface $paymentRepo,
    ) {
        $this->baseUrl = config('payments.mtn.base_url', '');
        $this->apiKey = config('payments.mtn.api_key', '');
        $this->merchantId = config('payments.mtn.merchant_id', '');
        $this->webhookSecret = config('payments.mtn.webhook_secret', '');
    }

    public function initiate(Booking $booking, string $phoneNumber = ''): PaymentInitiationResult
    {
        $payment = $this->paymentRepo->create([
            'booking_id'    => $booking->id,
            'user_id'       => $booking->user_id,
            'amount'        => $booking->deposit_amount ?: $booking->total_price,
            'currency'      => $booking->currency,
            'provider'      => PaymentProvider::MtnCash,
            'flow_type'     => PaymentFlowType::Otp,
            'status'        => 'pending',
            'initiated_at'  => now(),
            'provider_meta' => ['phone_number' => $phoneNumber],
        ]);

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type'  => 'application/json',
                ])
                ->post("{$this->baseUrl}/payments/initiate", [
                    'merchant_id'  => $this->merchantId,
                    'amount'       => $payment->amount,
                    'currency'     => $payment->currency,
                    'phone_number' => $phoneNumber,
                    'reference'    => $booking->booking_code,
                    'description'  => "حجز رقم {$booking->booking_code}",
                    'callback_url' => route('webhooks.mtn.callback'),
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->paymentRepo->update($payment, [
                    'provider_transaction_id' => $data['transaction_id'] ?? null,
                    'provider_payload'        => $data,
                ]);
            } else {
                Log::warning('MTN Cash initiation API error', [
                    'booking_id' => $booking->id,
                    'status'     => $response->status(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('MTN Cash initiation request failed', [
                'booking_id' => $booking->id,
                'error'      => $e->getMessage(),
            ]);
        }

        return new PaymentInitiationResult(
            payment: $payment->fresh(),
            flowType: PaymentFlowType::Otp,
            redirectUrl: null,
            otpChallengeUuid: null,
        );
    }

    public function confirm(Payment $payment, string $otp): bool
    {
        try {
            $transactionId = $payment->provider_transaction_id;

            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type'  => 'application/json',
                ])
                ->post("{$this->baseUrl}/payments/{$transactionId}/confirm", [
                    'otp' => $otp,
                ]);

            if ($response->successful() && strtoupper($response->json('status', '')) === 'SUCCESS') {
                $this->paymentRepo->update($payment, [
                    'status'           => 'completed',
                    'completed_at'     => now(),
                    'provider_payload' => $response->json(),
                ]);

                return true;
            }
        } catch (\Throwable $e) {
            Log::error('MTN Cash OTP confirmation failed', [
                'payment_id' => $payment->id,
                'error'      => $e->getMessage(),
            ]);
        }

        return false;
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
            'transaction_id' => $payload['transaction_id'] ?? '',
            'amount'         => (float) ($payload['amount'] ?? 0),
            'reference'      => $payload['reference'] ?? null,
        ];
    }

    public function checkStatus(string $transactionId): array
    {
        $response = Http::timeout(15)
            ->withHeaders(['Authorization' => "Bearer {$this->apiKey}"])
            ->get("{$this->baseUrl}/payments/{$transactionId}/status");

        if ($response->failed()) {
            throw new \RuntimeException('Failed to check MTN Cash payment status');
        }

        $data = $response->json();

        return [
            'status'         => $this->mapStatus($data['status'] ?? ''),
            'transaction_id' => $data['transaction_id'] ?? $transactionId,
            'amount'         => (float) ($data['amount'] ?? 0),
        ];
    }

    public function getName(): string
    {
        return 'mtn_cash';
    }

    private function mapStatus(string $gatewayStatus): string
    {
        return match (strtoupper($gatewayStatus)) {
            'SUCCESS', 'COMPLETED' => 'completed',
            'PROCESSING'           => 'processing',
            'FAILED', 'REJECTED'   => 'failed',
            'CANCELLED'            => 'cancelled',
            default                => 'pending',
        };
    }
}
