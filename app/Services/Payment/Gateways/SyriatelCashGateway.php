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

class SyriatelCashGateway implements PaymentGatewayInterface
{
    private string $baseUrl;

    private string $apiKey;

    private string $merchantCode;

    private string $webhookSecret;

    public function __construct(
        private PaymentRepositoryInterface $paymentRepo,
    ) {
        $this->baseUrl = config('payments.syriatel.base_url', '');
        $this->apiKey = config('payments.syriatel.api_key', '');
        $this->merchantCode = config('payments.syriatel.merchant_code', '');
        $this->webhookSecret = config('payments.syriatel.webhook_secret', '');
    }

    public function initiate(Booking $booking, string $phoneNumber = ''): PaymentInitiationResult
    {
        $payment = $this->paymentRepo->create([
            'booking_id'    => $booking->id,
            'user_id'       => $booking->user_id,
            'amount'        => $booking->deposit_amount ?: $booking->total_price,
            'currency'      => $booking->currency,
            'provider'      => PaymentProvider::SyriatelCash,
            'flow_type'     => PaymentFlowType::Otp,
            'status'        => 'pending',
            'initiated_at'  => now(),
            'provider_meta' => ['phone_number' => $phoneNumber],
        ]);

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-API-Key'    => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/api/v1/payment/request", [
                    'merchant_code' => $this->merchantCode,
                    'amount'        => $payment->amount,
                    'currency'      => $payment->currency,
                    'msisdn'        => $this->formatPhoneNumber($phoneNumber),
                    'order_id'      => $booking->booking_code,
                    'description'   => "حجز رقم {$booking->booking_code}",
                    'notify_url'    => route('webhooks.syriatel.callback'),
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->paymentRepo->update($payment, [
                    'provider_transaction_id' => $data['transaction_reference'] ?? null,
                    'provider_payload'        => $data,
                ]);
            } else {
                Log::warning('Syriatel Cash initiation API error', [
                    'booking_id' => $booking->id,
                    'status'     => $response->status(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Syriatel Cash initiation request failed', [
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
                    'X-API-Key'    => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/api/v1/payment/confirm", [
                    'transaction_reference' => $transactionId,
                    'otp'                  => $otp,
                ]);

            if ($response->successful() && strtoupper($response->json('transaction_status', '')) === 'SUCCESSFUL') {
                $this->paymentRepo->update($payment, [
                    'status'           => 'completed',
                    'completed_at'     => now(),
                    'provider_payload' => $response->json(),
                ]);

                return true;
            }
        } catch (\Throwable $e) {
            Log::error('Syriatel Cash OTP confirmation failed', [
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

        $dataString = ($payload['transaction_reference'] ?? '')
            . ($payload['order_id'] ?? '')
            . ($payload['amount'] ?? '');

        $computed = hash_hmac('sha256', $dataString, $this->webhookSecret);

        return hash_equals($computed, $signature);
    }

    public function processCallback(array $payload): array
    {
        return [
            'status'         => $this->mapStatus($payload['transaction_status'] ?? ''),
            'transaction_id' => $payload['transaction_reference'] ?? '',
            'amount'         => (float) ($payload['amount'] ?? 0),
            'reference'      => $payload['order_id'] ?? null,
        ];
    }

    public function checkStatus(string $transactionId): array
    {
        $response = Http::timeout(15)
            ->withHeaders(['X-API-Key' => $this->apiKey])
            ->get("{$this->baseUrl}/api/v1/payment/status/{$transactionId}");

        if ($response->failed()) {
            throw new \RuntimeException('Failed to check Syriatel Cash payment status');
        }

        $data = $response->json();

        return [
            'status'         => $this->mapStatus($data['status'] ?? ''),
            'transaction_id' => $data['transaction_reference'] ?? $transactionId,
            'amount'         => (float) ($data['amount'] ?? 0),
        ];
    }

    public function getName(): string
    {
        return 'syriatel_cash';
    }

    private function formatPhoneNumber(string $phone): string
    {
        // Convert +963944123456 → 0944123456
        if (str_starts_with($phone, '+963')) {
            return '0' . substr($phone, 4);
        }

        return $phone;
    }

    private function mapStatus(string $gatewayStatus): string
    {
        return match (strtoupper($gatewayStatus)) {
            'SUCCESSFUL', 'APPROVED' => 'completed',
            'PROCESSING'             => 'processing',
            'FAILED', 'DECLINED'     => 'failed',
            'CANCELLED'              => 'cancelled',
            default                  => 'pending',
        };
    }
}
