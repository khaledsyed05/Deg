<?php

namespace Tests\Feature\MobileEnvelope;

use Tests\MobileIntegrationTest;

/**
 * Phase 8 — Wallet + Coupons — 5 covered endpoints
 * (rest are gaps; Sprint 3+ work).
 *
 * - GET /wallet/account
 * - GET /wallet/transactions
 * - GET /wallet/settings
 * - PUT /wallet/settings
 * - POST /wallet/topup
 */
class Phase08WalletCouponsTest extends MobileIntegrationTest
{
    public function test_get_wallet_account_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->getJson('/api/v1/wallet/account');

        $this->assertEnvelope($response);
    }

    public function test_get_wallet_transactions_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->getJson('/api/v1/wallet/transactions');

        $this->assertEnvelope($response);
    }

    public function test_get_wallet_settings_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->getJson('/api/v1/wallet/settings');

        $this->assertEnvelope($response);
    }

    public function test_put_wallet_settings_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->putJson('/api/v1/wallet/settings', []);

        $this->assertEnvelope($response);
    }

    public function test_post_wallet_topup_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->postJson('/api/v1/wallet/topup', []);

        $this->assertErrorEnvelope($response, 422);
    }
}
