<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;

/**
 * Regression coverage for the Sprint 1 verification finding:
 * POST /api/v1/auth/logout returned HTTP 500 because the controller
 * called ->delete() on whatever currentAccessToken() returned. Under
 * actingAs($user, 'sanctum') in tests that's a TransientToken which
 * doesn't have a delete() method.
 */
class LogoutTest extends TestCase
{
    public function test_logout_returns_envelope_under_acting_as_helper(): void
    {
        $user = User::factory()->create();
        $user->assignRole('player');
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => null,
        ]);
        $response->assertJsonStructure(['success', 'message', 'data', 'errors']);
    }

    public function test_logout_revokes_a_real_personal_access_token(): void
    {
        $user = User::factory()->create();
        $user->assignRole('player');
        $token = $user->createToken('mobile-app');
        $tokenId = $token->accessToken->id;

        $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/auth/logout');

        $response->assertOk();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    }
}
