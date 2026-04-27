<?php

namespace App\DTOs\Payment;

readonly class WalletTopupInitiationResult
{
    /**
     * @param  'enter_otp'|'redirect_to_url'|'completed'  $nextStep
     * @param  array<string, mixed>  $responseData
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $nextStep,
        public ?string $providerReference = null,
        public ?string $redirectUrl = null,
        public ?string $maskedPhone = null,
        public ?int $expiresInSeconds = null,
        public array $responseData = [],
        public array $metadata = [],
    ) {}
}
