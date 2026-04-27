<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorShapeTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_404_endpoint_returns_envelope(): void
    {
        $response = $this->getJson('/api/v1/this-route-does-not-exist');

        $response->assertStatus(404)
            ->assertJsonStructure(['success', 'message'])
            ->assertJsonPath('success', false);
    }

    public function test_405_method_not_allowed_returns_envelope(): void
    {
        $response = $this->postJson('/api/v1/venues');

        $response->assertStatus(405)
            ->assertJsonPath('success', false);
    }

    public function test_401_when_unauth_hits_protected_route(): void
    {
        $this->getJson('/api/v1/profile')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_403_role_blocked_returns_envelope(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/admin/v1/dashboard/stats')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_validation_422_returns_envelope_with_errors(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/emergency/report', [])
            ->assertStatus(422)
            ->assertJsonStructure(['success', 'message', 'errors'])
            ->assertJsonPath('success', false);
    }

    public function test_unhandled_exception_reshaped_to_500(): void
    {
        Route::get('/api/_test/boom', function () {
            throw new \RuntimeException('boom-internal');
        })->middleware('api');

        $response = $this->getJson('/api/_test/boom');

        $response->assertStatus(500)
            ->assertJsonStructure(['success', 'message'])
            ->assertJsonPath('success', false);
    }

    public function test_middleware_reshapes_raw_4xx_response(): void
    {
        Route::get('/api/_test/raw-403', function () {
            return response()->json(['error' => 'nope'], 403);
        })->middleware('api');

        $this->getJson('/api/_test/raw-403')
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'nope');
    }

    public function test_successful_response_passthrough(): void
    {
        Route::get('/api/_test/ok', function () {
            return response()->json(['success' => true, 'data' => ['hello' => 'world']]);
        })->middleware('api');

        $this->getJson('/api/_test/ok')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.hello', 'world');
    }
}
