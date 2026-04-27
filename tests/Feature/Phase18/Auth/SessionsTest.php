<?php

namespace Tests\Feature\Phase18\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SessionsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sessions_lists_tokens_with_current_marked(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('iPhone-14-Pro');
        $user->createToken('Samsung-Galaxy');

        $response = $this->withHeader('Authorization', 'Bearer '.$current->plainTextToken)
            ->getJson('/api/v1/auth/sessions')
            ->assertOk();

        $sessions = $response->json('data.data');
        $this->assertGreaterThanOrEqual(2, count($sessions));
        $this->assertSame($current->accessToken->id, $response->json('data.meta.current_session_id'));
    }

    public function test_revoke_current_session_blocked(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('current');
        $tokenId = $token->accessToken->id;

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->deleteJson("/api/v1/auth/sessions/{$tokenId}")
            ->assertStatus(422);
    }

    public function test_revoke_other_session_succeeds(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('current');
        $other = $user->createToken('other-device');
        $otherId = $other->accessToken->id;

        $this->withHeader('Authorization', 'Bearer '.$current->plainTextToken)
            ->deleteJson("/api/v1/auth/sessions/{$otherId}")
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $otherId]);
    }

    public function test_revoke_nonexistent_session_returns_404(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('current');

        $this->withHeader('Authorization', 'Bearer '.$current->plainTextToken)
            ->deleteJson('/api/v1/auth/sessions/99999')
            ->assertStatus(404);
    }
}
