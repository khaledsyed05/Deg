<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Auth\FirebaseAuthService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_can_register_and_receive_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'أحمد محمد',
            'phone_number' => '+963944100001',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'data' => ['user', 'token'],
            ]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/v1/auth/logout');

        $response->assertOk();
        $this->assertCount(0, $user->tokens()->get());
    }

    public function test_unauthenticated_logout_returns_401(): void
    {
        $this->postJson('/api/v1/auth/logout')->assertUnauthorized();
    }

    public function test_otp_send_requires_phone_number(): void
    {
        $response = $this->postJson('/api/v1/auth/otp/send', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_google_signin_creates_new_user(): void
    {
        $this->mock(FirebaseAuthService::class, function (MockInterface $mock) {
            $mock->shouldReceive('verifyIdToken')
                ->once()
                ->andReturn([
                    'uid' => 'firebase-test-uid',
                    'email' => 'test@gmail.com',
                    'phone' => null,
                    'name' => 'Test User',
                    'picture' => 'https://example.com/avatar.jpg',
                ]);
        });

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => 'fake-google-token',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['auth_outcome', 'access_token', 'user', 'onboarding_prefill'],
            ])
            ->assertJsonPath('data.auth_outcome', 'registered')
            ->assertJsonPath('data.onboarding_prefill.avatar_url', 'https://example.com/avatar.jpg');

        $this->assertDatabaseHas('users', [
            'email' => 'test@gmail.com',
            'firebase_uid' => 'firebase-test-uid',
        ]);
    }

    public function test_refresh_token_revokes_current_and_issues_new(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile-app')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/v1/auth/refresh');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['access_token', 'token_type', 'expires_in']]);

        $this->assertNotEmpty($response->json('data.access_token'));
        $this->assertCount(1, $user->tokens()->get());
    }

    public function test_logout_all_revokes_every_token(): void
    {
        $user = User::factory()->create();
        $token1 = $user->createToken('device-1')->plainTextToken;
        $user->createToken('device-2');

        $this->withToken($token1)->postJson('/api/v1/auth/logout-all')->assertOk();

        $this->assertCount(0, $user->tokens()->get());
    }
}
