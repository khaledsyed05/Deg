<?php

namespace Tests\Feature;

use Tests\MobileIntegrationTest;

/**
 * Sprint 0 smoke tests for the MobileIntegrationTest base class.
 *
 * Exercises the envelope assertion helpers against existing endpoints to
 * surface where the live responses diverge from the documented envelope.
 *
 * Failures here are intentional Sprint-0 audit signal — do not "fix" the
 * endpoints in this sprint. Sprint 1 (Verification Pass) consumes these
 * findings.
 */
class MobileIntegrationBaseTest extends MobileIntegrationTest
{
    public function test_envelope_assertion_against_categories_index(): void
    {
        $response = $this->getJson('/api/v1/categories');

        $response->assertOk();
        $this->assertEnvelope($response);
    }

    public function test_paginated_envelope_against_venues_index(): void
    {
        $response = $this->getJson('/api/v1/venues');

        $response->assertOk();
        $this->assertPaginatedEnvelope($response);
    }

    public function test_error_envelope_against_otp_send_with_invalid_payload(): void
    {
        $response = $this->postJson('/api/v1/auth/otp/send', []);

        $this->assertErrorEnvelope($response, 422);
    }

    public function test_acting_as_player_assigns_role(): void
    {
        $user = $this->actingAsRole('player');

        $this->assertTrue($user->hasRole('player'));
        $this->assertAuthenticatedAs($user, 'sanctum');
    }

    public function test_acting_as_club_manager_assigns_role(): void
    {
        $user = $this->actingAsRole('club_manager');

        $this->assertTrue($user->hasRole('club_manager'));
        $this->assertAuthenticatedAs($user, 'sanctum');
    }

    public function test_acting_as_club_staff_assigns_role(): void
    {
        $user = $this->actingAsRole('club_staff');

        $this->assertTrue($user->hasRole('club_staff'));
        $this->assertAuthenticatedAs($user, 'sanctum');
    }

    public function test_acting_as_admin_assigns_role(): void
    {
        $user = $this->actingAsRole('admin');

        $this->assertTrue($user->hasRole('admin'));
        $this->assertAuthenticatedAs($user, 'sanctum');
    }
}
