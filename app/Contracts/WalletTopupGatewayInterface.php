<?php

namespace App\Contracts;

use App\DTOs\Payment\WalletTopupConfirmResult;
use App\DTOs\Payment\WalletTopupInitiationResult;
use App\Models\Payment;

interface WalletTopupGatewayInterface
{
    /**
     * Initiate a wallet top-up. Implementations must NOT credit the wallet —
     * they only kick off the provider's payment flow.
     *
     * @param  array{phone?: string, amount: int, user_id: int, wallet_id: int}  $context
     */
    public function initiate(Payment $payment, array $context): WalletTopupInitiationResult;

    /**
     * Confirm a pending top-up payment with an OTP (Syriatel/MTN).
     */
    public function confirm(Payment $payment, string $otp): WalletTopupConfirmResult;

    /**
     * Resend an OTP for a pending top-up. Returns true on success.
     */
    public function resendOtp(Payment $payment): bool;

    /**
     * Verify and parse a webhook callback. Used by hosted-flow gateways (bank).
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleCallback(array $payload): WalletTopupConfirmResult;
}
