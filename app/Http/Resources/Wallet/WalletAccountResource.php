<?php

namespace App\Http\Resources\Wallet;

use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Wallet
 */
class WalletAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $settings = is_array($this->settings) ? $this->settings : [];

        return [
            'balance' => (int) $this->balance,
            'locked_amount' => (int) $this->locked,
            'available_balance' => (int) $this->available,
            'currency' => $this->currency ?: 'SYP',
            'total_earned' => (int) $this->total_earned,
            'total_spent' => (int) $this->total_spent,
            'total_topup' => (int) $this->total_topup,
            'auto_topup' => [
                'enabled' => (bool) ($settings['auto_topup_enabled'] ?? false),
                'threshold' => isset($settings['auto_topup_threshold'])
                    ? (int) $settings['auto_topup_threshold']
                    : null,
                'amount' => isset($settings['auto_topup_amount'])
                    ? (int) $settings['auto_topup_amount']
                    : null,
                'payment_method' => $settings['auto_topup_payment_method'] ?? null,
                'low_balance_alert' => (bool) ($settings['low_balance_alert'] ?? false),
            ],
        ];
    }
}
