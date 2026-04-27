<?php

namespace App\Services\Wallet\Gateways;

use App\Contracts\WalletTopupGatewayInterface;
use App\Enums\PaymentProvider;

class WalletTopupGatewayFactory
{
    public function __construct(
        private SyriatelWalletGateway $syriatel,
        private MtnWalletGateway $mtn,
        private BankWalletGateway $bank,
    ) {}

    /**
     * Resolve a gateway from the user-facing method key (`syriatel`, `mtn`, `bank`).
     */
    public function resolve(string $method): WalletTopupGatewayInterface
    {
        return match ($method) {
            'syriatel', 'syriatel_cash' => $this->syriatel,
            'mtn', 'mtn_cash' => $this->mtn,
            'bank', 'fatora', 'albaraka' => $this->bank,
            default => throw new \InvalidArgumentException("طريقة دفع غير مدعومة: {$method}"),
        };
    }

    public function methodToProvider(string $method): PaymentProvider
    {
        return match ($method) {
            'syriatel', 'syriatel_cash' => PaymentProvider::SyriatelCash,
            'mtn', 'mtn_cash' => PaymentProvider::MtnCash,
            'bank', 'fatora', 'albaraka' => PaymentProvider::Fatora,
            default => throw new \InvalidArgumentException("طريقة دفع غير مدعومة: {$method}"),
        };
    }
}
