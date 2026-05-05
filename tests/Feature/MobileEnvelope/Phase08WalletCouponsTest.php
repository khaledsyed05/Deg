<?php

namespace Tests\Feature\MobileEnvelope;

use Tests\MobileIntegrationTest;

/**
 * Phase 8 — Wallet + Coupons.
 *
 * Sprint 1 only verified envelope shape; Sprint 3 strengthens the
 * assertions to full per-resource data shape per
 * BACKEND_REQUIREMENTS.md Phase 8.
 */
class Phase08WalletCouponsTest extends MobileIntegrationTest
{
    public function test_get_wallet_account_returns_documented_shape(): void
    {
        $this->actingAsRole('player');

        $response = $this->getJson('/api/v1/wallet/account');

        $response->assertOk();
        $this->assertEnvelope($response);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'balance',
                'locked_amount',
                'available_balance',
                'currency',
                'total_earned',
                'total_spent',
                'total_topup',
                'auto_topup' => [
                    'enabled',
                    'threshold',
                    'amount',
                    'payment_method',
                    'low_balance_alert',
                ],
            ],
            'errors',
        ]);
    }

    public function test_get_wallet_transactions_returns_paginated_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->getJson('/api/v1/wallet/transactions');

        $response->assertOk();
        $this->assertPaginatedEnvelope($response);
    }

    public function test_get_wallet_settings_returns_documented_shape(): void
    {
        $this->actingAsRole('player');

        $response = $this->getJson('/api/v1/wallet/settings');

        $response->assertOk();
        $this->assertEnvelope($response);
        $response->assertJsonStructure([
            'data' => [
                'auto_topup_enabled',
                'auto_topup_threshold',
                'auto_topup_amount',
                'auto_topup_payment_method',
                'low_balance_alert',
            ],
        ]);
    }

    public function test_put_wallet_settings_persists_and_returns_documented_shape(): void
    {
        $this->actingAsRole('player');

        $response = $this->putJson('/api/v1/wallet/settings', [
            'auto_topup_enabled' => true,
            'auto_topup_threshold' => 50000,
            'auto_topup_amount' => 100000,
            'auto_topup_payment_method' => 'syriatel_cash',
            'low_balance_alert' => true,
        ]);

        $response->assertOk();
        $this->assertEnvelope($response);
        $response->assertJson([
            'data' => [
                'auto_topup_enabled' => true,
                'auto_topup_threshold' => 50000,
                'auto_topup_amount' => 100000,
                'auto_topup_payment_method' => 'syriatel_cash',
                'low_balance_alert' => true,
            ],
        ]);
    }

    public function test_post_wallet_topup_returns_validation_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->postJson('/api/v1/wallet/topup', []);

        $this->assertErrorEnvelope($response, 422);
    }
}
