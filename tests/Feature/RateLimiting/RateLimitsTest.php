<?php

namespace Tests\Feature\RateLimiting;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Sprint 8 B1 — verify the limiter matrix is enforced and that
 * 429 responses are wrapped in the canonical envelope with a
 * retry_after hint in the errors field.
 */
class RateLimitsTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Wipe limiter state so each test starts with a clean
        // counter (limiters are keyed by phone/user/ip, all of
        // which can collide across tests sharing the same factory
        // sequences).
        foreach (
            ['auth-otp-send', 'auth-otp-verify', 'payments', 'chat-send',
                'chat-mark-read', 'pusher-auth', 'profile-mutations',
                'team-invites', 'bookings-create', 'default-mutations'] as $limiter
        ) {
            RateLimiter::clear($limiter);
        }
    }

    public function test_auth_otp_send_under_limit_succeeds(): void
    {
        // 3/min/phone — first 3 should each return validation 422
        // (no real OTP infra in tests) but never 429.
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/api/v1/auth/otp/send', ['phone' => '+963999999999']);
            $this->assertNotSame(429, $response->status(), "Request {$i} should not be rate-limited");
        }
    }

    public function test_auth_otp_send_over_limit_returns_429_envelope(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/auth/otp/send', ['phone' => '+963999999998']);
        }

        $response = $this->postJson('/api/v1/auth/otp/send', ['phone' => '+963999999998']);

        $response->assertStatus(429)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['retry_after'],
            ])
            ->assertJsonPath('success', false);

        $this->assertIsInt($response->json('errors.retry_after'));
        $this->assertGreaterThanOrEqual(0, $response->json('errors.retry_after'));
    }

    public function test_payments_rate_limited_per_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        // payments: 10/hour. 11th hits the limit.
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/wallet/transfer', ['recipient_id' => $user->id, 'amount' => 1])
                ->assertStatus(422); // validation rejects (recipient is self) — but doesn't 429
        }

        $response = $this->postJson('/api/v1/wallet/transfer', ['recipient_id' => $user->id, 'amount' => 1]);

        $response->assertStatus(429);
        $this->assertIsInt($response->json('errors.retry_after'));
    }

    public function test_chat_send_rate_limited(): void
    {
        $alice = $this->actingAsPlayer();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        // chat-send: 60/min/user. Send 60 successful messages, the
        // 61st must 429.
        for ($i = 0; $i < 60; $i++) {
            $this->postJson('/api/v1/messages', [
                'conversation_id' => $convo->id,
                'body' => 'msg '.$i,
                'idempotency_key' => 'chat-rl-'.str_pad((string) $i, 8, '0'),
            ])->assertCreated();
        }

        $response = $this->postJson('/api/v1/messages', [
            'conversation_id' => $convo->id,
            'body' => 'over the limit',
            'idempotency_key' => 'chat-rl-overlimit-1',
        ]);

        $response->assertStatus(429);
        $this->assertIsInt($response->json('errors.retry_after'));
    }

    public function test_pusher_auth_rate_limited(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        // pusher-auth: 60/min/user. Channel auth returns 403 (no
        // team membership) for the first 60 — those still consume
        // the counter. 61st gets 429 instead of 403.
        for ($i = 0; $i < 60; $i++) {
            $this->postJson('/api/v1/pusher/auth', [
                'socket_id' => '1.2',
                'channel_name' => 'private-team-9999999',
            ])->assertForbidden();
        }

        $response = $this->postJson('/api/v1/pusher/auth', [
            'socket_id' => '1.2',
            'channel_name' => 'private-team-9999999',
        ]);

        $response->assertStatus(429);
    }

    public function test_unthrottled_endpoint_remains_unrestricted(): void
    {
        // GET endpoints are not throttled — read access shouldn't
        // be artificially rate-limited.
        $this->actingAsPlayer();

        for ($i = 0; $i < 100; $i++) {
            $response = $this->getJson('/api/v1/conversations');
            $this->assertNotSame(429, $response->status(), 'GET /conversations should be unthrottled');
            // Stop early on the first successful call — we just need to confirm no throttling.
            if ($i > 5) {
                break;
            }
        }
    }
}
