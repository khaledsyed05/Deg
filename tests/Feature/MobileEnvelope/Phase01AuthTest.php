<?php

namespace Tests\Feature\MobileEnvelope;

use Tests\MobileIntegrationTest;

/**
 * Phase 1 — Auth & Profile (12 endpoints).
 *
 * Each test hits the endpoint with a minimal payload and asserts the
 * documented envelope from BACKEND_REQUIREMENTS.md. Where the spec's
 * Response example is an error (422 / 401), we assert an error envelope
 * with the documented status. Where the spec shows a happy-path body,
 * we authenticate and assert the success envelope.
 *
 * Failures here are verification signal for Sprint 2+.
 */
class Phase01AuthTest extends MobileIntegrationTest
{
    public function test_post_auth_otp_send_returns_validation_envelope(): void
    {
        $response = $this->postJson('/api/v1/auth/otp/send', []);

        $this->assertErrorEnvelope($response, 422);
    }

    public function test_post_auth_otp_verify_returns_validation_envelope(): void
    {
        $response = $this->postJson('/api/v1/auth/otp/verify', []);

        $this->assertErrorEnvelope($response, 422);
    }

    public function test_post_auth_otp_resend_returns_validation_envelope(): void
    {
        $response = $this->postJson('/api/v1/auth/otp/resend', []);

        $this->assertErrorEnvelope($response, 422);
    }

    public function test_post_auth_register_returns_documented_response(): void
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $this->assertErrorEnvelope($response, 422);
    }

    public function test_post_auth_google_returns_validation_envelope(): void
    {
        $response = $this->postJson('/api/v1/auth/google', []);

        $this->assertErrorEnvelope($response, 422);
    }

    public function test_post_auth_logout_returns_success_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertOk();
        $this->assertEnvelope($response);
    }

    public function test_get_profile_returns_success_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->getJson('/api/v1/profile');

        $response->assertOk();
        $this->assertEnvelope($response);
    }

    public function test_put_profile_returns_success_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->putJson('/api/v1/profile', [
            'name' => 'Test Player',
        ]);

        $response->assertOk();
        $this->assertEnvelope($response);
    }

    public function test_post_profile_avatar_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->postJson('/api/v1/profile/avatar', []);

        $this->assertErrorEnvelope($response, 422);
    }

    public function test_delete_profile_avatar_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->deleteJson('/api/v1/profile/avatar');

        $this->assertEnvelope($response);
    }

    public function test_post_devices_returns_validation_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->postJson('/api/v1/devices', []);

        $this->assertErrorEnvelope($response, 422);
    }

    public function test_post_auth_refresh_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->postJson('/api/v1/auth/refresh', []);

        $this->assertEnvelope($response);
    }
}
