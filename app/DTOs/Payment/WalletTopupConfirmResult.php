<?php

namespace App\DTOs\Payment;

readonly class WalletTopupConfirmResult
{
    /**
     * @param  array<string, mixed>  $providerData
     */
    public function __construct(
        public bool $success,
        public ?string $transactionId = null,
        public ?string $errorMessage = null,
        public array $providerData = [],
    ) {}
}
