<?php

namespace App\Services\Wallet\Gateways;

use App\Contracts\WalletTopupGatewayInterface;
use App\DTOs\Payment\WalletTopupConfirmResult;
use App\DTOs\Payment\WalletTopupInitiationResult;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MtnWalletGateway implements WalletTopupGatewayInterface
{
    public function initiate(Payment $payment, array $context): WalletTopupInitiationResult
    {
        $config = $this->config();
        $phone = $context['phone'] ?? '';

        $providerRef = 'WTU-MTN-'.$payment->id.'-'.bin2hex(random_bytes(4));

        Log::channel('payments')->info('wallet_topup.mtn.initiate', [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'phone' => $this->mask($phone),
        ]);

        $response = Http::timeout(15)
            ->withHeaders(['X-API-Key' => $config['api_key']])
            ->post(rtrim($config['base_url'], '/').'/cashmobile/initiate', [
                'merchantId' => $config['merchant_id'],
                'amount' => $payment->amount,
                'currency' => $payment->currency ?? 'SYP',
                'msisdn' => $phone,
                'reference' => $providerRef,
                'description' => 'Wallet top-up',
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('فشل بدء الدفع عبر MTN Cash');
        }

        return new WalletTopupInitiationResult(
            nextStep: 'enter_otp',
            providerReference: $providerRef,
            maskedPhone: $this->mask($phone),
            expiresInSeconds: 300,
            responseData: ['masked_phone' => $this->mask($phone), 'expires_in' => 300],
            metadata: ['provider_response' => $response->json()],
        );
    }

    public function confirm(Payment $payment, string $otp): WalletTopupConfirmResult
    {
        $config = $this->config();

        Log::channel('payments')->info('wallet_topup.mtn.confirm', ['payment_id' => $payment->id]);

        $response = Http::timeout(15)
            ->withHeaders(['X-API-Key' => $config['api_key']])
            ->post(rtrim($config['base_url'], '/').'/cashmobile/confirm', [
                'merchantId' => $config['merchant_id'],
                'reference' => $payment->provider_reference,
                'otp' => $otp,
            ]);

        $data = $response->json() ?? [];

        if (! $response->successful() || (int) ($data['errorCode'] ?? 1) !== 0) {
            return new WalletTopupConfirmResult(
                success: false,
                errorMessage: $data['errorDesc'] ?? 'فشل تأكيد الدفع',
                providerData: $data,
            );
        }

        return new WalletTopupConfirmResult(
            success: true,
            transactionId: (string) ($data['transactionId'] ?? $payment->provider_reference),
            providerData: $data,
        );
    }

    public function resendOtp(Payment $payment): bool
    {
        $config = $this->config();

        $response = Http::timeout(15)
            ->withHeaders(['X-API-Key' => $config['api_key']])
            ->post(rtrim($config['base_url'], '/').'/cashmobile/resend-otp', [
                'merchantId' => $config['merchant_id'],
                'reference' => $payment->provider_reference,
            ]);

        return $response->successful();
    }

    public function handleCallback(array $payload): WalletTopupConfirmResult
    {
        return new WalletTopupConfirmResult(success: false, errorMessage: 'MTN uses OTP confirm, not webhook');
    }

    /**
     * @return array{base_url: string, api_key: string, merchant_id: string}
     */
    private function config(): array
    {
        $base = (string) config('payments.mtn.base_url');
        $key = (string) config('payments.mtn.api_key');
        $merchant = (string) config('payments.mtn.merchant_id');

        if ($base === '' || $key === '' || $merchant === '') {
            throw new \RuntimeException('MTN Cash لم يُكوَّن (راجع PAYMENT_MTN_* في .env)');
        }

        return ['base_url' => $base, 'api_key' => $key, 'merchant_id' => $merchant];
    }

    private function mask(string $phone): string
    {
        if (strlen($phone) < 6) {
            return $phone;
        }

        return substr($phone, 0, 4).str_repeat('*', max(0, strlen($phone) - 6)).substr($phone, -2);
    }
}
