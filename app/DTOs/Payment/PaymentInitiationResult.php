<?php

namespace App\DTOs\Payment;

use App\Enums\PaymentFlowType;
use App\Models\Payment;

readonly class PaymentInitiationResult
{
    public function __construct(
        public Payment $payment,
        public PaymentFlowType $flowType,
        public ?string $redirectUrl,
        public ?string $otpChallengeUuid,
        public array $meta = [],
    ) {}
}
