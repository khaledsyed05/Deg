<?php

namespace App\DTOs\Payment;

readonly class WalletBalanceResult
{
    public function __construct(
        public int $balance,
        public string $currency,
        public int $walletId,
    ) {}
}
