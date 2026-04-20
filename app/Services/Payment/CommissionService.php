<?php

namespace App\Services\Payment;

use App\DTOs\Payment\CommissionResult;
use App\Enums\CommissionType;
use App\Models\CommissionConfig;
use App\Repositories\Contracts\CommissionConfigRepositoryInterface;
use RuntimeException;

class CommissionService
{
    public function __construct(
        private CommissionConfigRepositoryInterface $configRepo,
    ) {}

    public function resolveConfig(int $venueId): CommissionConfig
    {
        $config = $this->configRepo->findForVenue($venueId);

        if (! $config) {
            throw new RuntimeException('No active commission configuration found.');
        }

        return $config;
    }

    public function calculate(int $venuePrice, CommissionConfig $config): CommissionResult
    {
        // Commission is ALWAYS deducted from club payout (LOCK-001)
        $commissionAmount = match ($config->commission_type) {
            CommissionType::Fixed => $config->commission_value,
            // commission_value stored as basis points: 700 = 7.00%
            CommissionType::Percentage => (int) round($venuePrice * $config->commission_value / 10000),
        };

        return new CommissionResult(
            totalPrice: $venuePrice,
            commissionAmount: $commissionAmount,
            clubPayoutAmount: $venuePrice - $commissionAmount,
            commissionType: $config->commission_type,
            configId: $config->id,
        );
    }

    public function calculateForDeposit(int $depositAmount, CommissionConfig $config): CommissionResult
    {
        // Commission calculated on deposit portion only
        return $this->calculate($depositAmount, $config);
    }
}
