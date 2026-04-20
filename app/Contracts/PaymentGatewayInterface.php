<?php

namespace App\Contracts;

use App\DTOs\Payment\PaymentInitiationResult;
use App\Models\Booking;
use App\Models\Payment;

interface PaymentGatewayInterface
{
    /**
     * Initiate a payment transaction for a booking.
     */
    public function initiate(Booking $booking, string $phoneNumber = ''): PaymentInitiationResult;

    /**
     * Verify webhook signature from the gateway.
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool;

    /**
     * Normalize a raw webhook payload into a standard result.
     *
     * @return array{status: string, transaction_id: string, amount: float, reference?: string|null}
     */
    public function processCallback(array $payload): array;

    /**
     * Poll the gateway for the current status of a transaction.
     *
     * @return array{status: string, transaction_id: string, amount: float}
     */
    public function checkStatus(string $transactionId): array;

    /**
     * Return the canonical gateway name matching PaymentProvider enum value.
     */
    public function getName(): string;
}
