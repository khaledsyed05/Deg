<?php

namespace App\Services\Wallet\Gateways;

use App\Contracts\WalletTopupGatewayInterface;
use App\DTOs\Payment\WalletTopupConfirmResult;
use App\DTOs\Payment\WalletTopupInitiationResult;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

/**
 * AlBaraka / Fatora hosted-page bank flow for wallet top-up.
 * Initiation builds a hosted-payment URL; completion arrives via webhook.
 */
class BankWalletGateway implements WalletTopupGatewayInterface
{
    public function initiate(Payment $payment, array $context): WalletTopupInitiationResult
    {
        $config = $this->config();

        $providerRef = 'WTU-BNK-'.$payment->id.'-'.bin2hex(random_bytes(4));

        Log::channel('payments')->info('wallet_topup.bank.initiate', [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
        ]);

        $params = [
            'pspId' => $config['psp_id'],
            'mpiId' => $config['mpi_id'],
            'merchantKitId' => $config['merchant_kit_id'],
            'authToken' => $config['auth_token'],
            'cardAcceptor' => $config['card_acceptor'],
            'mcc' => $config['mcc'],
            'amount' => $payment->amount,
            'currency' => $payment->currency ?? 'SYP',
            'transactionReference' => $providerRef,
            'callbackUrl' => $config['callback_url'],
            'redirectUrl' => $config['redirect_url'],
        ];

        $hostedUrl = rtrim($config['hosted_url'], '/').'?'.http_build_query($params);

        return new WalletTopupInitiationResult(
            nextStep: 'redirect_to_url',
            providerReference: $providerRef,
            redirectUrl: $hostedUrl,
            responseData: ['payment_url' => $hostedUrl],
            metadata: ['hosted_url_params' => array_diff_key($params, array_flip(['authToken']))],
        );
    }

    public function confirm(Payment $payment, string $otp): WalletTopupConfirmResult
    {
        return new WalletTopupConfirmResult(
            success: false,
            errorMessage: 'Bank flow uses webhook callback, not OTP confirm',
        );
    }

    public function resendOtp(Payment $payment): bool
    {
        return false;
    }

    public function handleCallback(array $payload): WalletTopupConfirmResult
    {
        $txId = $payload['idTransaction'] ?? $payload['transactionReference'] ?? null;
        $status = strtoupper((string) ($payload['transactionStat'] ?? $payload['status'] ?? ''));

        if (! $txId) {
            return new WalletTopupConfirmResult(success: false, errorMessage: 'Missing transaction reference');
        }

        $isSuccess = in_array($status, ['CAPTURED', 'AUTHORIZED', 'SUCCESS', 'COMPLETED'], true);

        return new WalletTopupConfirmResult(
            success: $isSuccess,
            transactionId: (string) $txId,
            errorMessage: $isSuccess ? null : "Bank declined ({$status})",
            providerData: $payload,
        );
    }

    public function verifyCallbackSignature(array $payload): bool
    {
        $config = $this->config();
        $sig = $payload['signature'] ?? null;
        if (! $sig) {
            return false;
        }
        // AlBaraka: HMAC-SHA256 of canonicalized payload using auth_token as key.
        unset($payload['signature']);
        ksort($payload);
        $canonical = http_build_query($payload);
        $expected = hash_hmac('sha256', $canonical, (string) $config['auth_token']);

        return hash_equals($expected, (string) $sig);
    }

    /**
     * @return array<string, string>
     */
    private function config(): array
    {
        $cfg = (array) config('payments.albaraka', []);
        $required = ['psp_id', 'mpi_id', 'merchant_kit_id', 'auth_token', 'card_acceptor', 'hosted_url', 'callback_url', 'redirect_url'];
        foreach ($required as $key) {
            if (empty($cfg[$key])) {
                throw new \RuntimeException("AlBaraka لم يُكوَّن (مفتاح ناقص: {$key})");
            }
        }
        $cfg['mcc'] = $cfg['mcc'] ?? '7011';

        return $cfg;
    }
}
