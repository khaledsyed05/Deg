<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_complete_profile_requires_auth(): void
    {
        $this->postJson('/api/v1/auth/complete-profile', [
            'name' => 'أحمد',
            'date_of_birth' => '1995-06-15',
        ])->assertUnauthorized();
    }

    public function test_complete_profile_sets_name_and_dob(): void
    {
        $user = User::factory()->create(['name' => null, 'date_of_birth' => null]);
        $token = $user->createToken('mobile-app')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/auth/complete-profile', [
                'name' => 'أحمد محمد',
                'date_of_birth' => '1995-06-15',
            ])
            ->assertOk()
            ->assertJsonStructure(['success', 'data' => ['user']])
            ->assertJsonPath('data.user.name', 'أحمد محمد')
            ->assertJsonPath('data.user.date_of_birth', '1995-06-15');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'أحمد محمد']);
        $this->assertSame('1995-06-15', $user->fresh()->date_of_birth->toDateString());
    }

    public function test_complete_profile_requires_name(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile-app')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/auth/complete-profile', [
                'date_of_birth' => '1995-06-15',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_complete_profile_requires_date_of_birth(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile-app')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/auth/complete-profile', [
                'name' => 'أحمد محمد',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date_of_birth']);
    }

    public function test_complete_profile_rejects_future_dob(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile-app')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/auth/complete-profile', [
                'name' => 'أحمد محمد',
                'date_of_birth' => now()->addDay()->toDateString(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date_of_birth']);
    }

    public function test_complete_profile_sets_onboarding_completed_at(): void
    {
        $user = User::factory()->create(['onboarding_completed_at' => null]);
        $token = $user->createToken('mobile-app')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/auth/complete-profile', [
                'name' => 'أحمد محمد',
                'date_of_birth' => '1990-01-01',
            ])
            ->assertOk();

        $this->assertNotNull($user->fresh()->onboarding_completed_at);
    }

    public function test_complete_profile_response_does_not_expose_password(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile-app')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/v1/auth/complete-profile', [
                'name' => 'أحمد محمد',
                'date_of_birth' => '1990-01-01',
            ])
            ->assertOk();

        $this->assertArrayNotHasKey('password', $response->json('data.user'));
    }
}
