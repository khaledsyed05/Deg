<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @var array<string, string> */
    private array $valid = [
        'name'                  => 'Test User',
        'phone_number'          => '+963944123456',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
    ];

    public function test_registers_a_user_successfully_and_returns_token_and_user_data(): void
    {
        $this->postJson('/api/v1/auth/register', $this->valid)
            ->assertCreated()
            ->assertJsonStructure([
                'success',
                'data' => ['user', 'token'],
            ]);

        $this->assertDatabaseHas('users', ['phone_number' => '+963944123456']);
    }

    public function test_duplicate_phone_number_returns_validation_error(): void
    {
        User::factory()->create(['phone_number' => '+963944123456']);

        $this->postJson('/api/v1/auth/register', $this->valid)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone_number']);
    }

    public function test_invalid_phone_number_returns_validation_error(): void
    {
        $this->postJson('/api/v1/auth/register', array_merge($this->valid, ['phone_number' => 'not-a-phone']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone_number']);
    }

    public function test_password_confirmation_mismatch_returns_validation_error(): void
    {
        $this->postJson('/api/v1/auth/register', array_merge($this->valid, ['password_confirmation' => 'wrong']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_registered_user_has_player_spatie_role(): void
    {
        $this->postJson('/api/v1/auth/register', $this->valid)->assertCreated();

        $user = User::where('phone_number', '+963944123456')->first();
        $this->assertTrue($user->hasRole('player'));
    }

    public function test_response_does_not_expose_sensitive_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->valid)->assertCreated();

        $userData = $response->json('data.user');
        $this->assertArrayNotHasKey('password', $userData);
    }
}
