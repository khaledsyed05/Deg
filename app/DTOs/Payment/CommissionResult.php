<?php

namespace App\DTOs\Payment;

use App\Enums\CommissionType;

readonly class CommissionResult
{
    public function __construct(
        public int $totalPrice,
        public int $commissionAmount,
        public int $clubPayoutAmount,
        public CommissionType $commissionType,
        public int $configId,
    ) {}
}
