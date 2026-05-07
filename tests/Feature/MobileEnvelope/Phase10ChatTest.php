<?php

namespace Tests\Feature\MobileEnvelope;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Tests\MobileIntegrationTest;

/**
 * Phase 10 — Chat (Pusher) — 9 endpoints (all live as of Sprint 7).
 *
 * Sprint 1 baseline: 4 of these were tested as canonical-404 (route
 * absent). Sprint 7 wires the full chat suite; all 9 flip to
 * success + envelope.
 *
 * - GET    /conversations
 * - GET    /conversations/{id}
 * - GET    /conversations/{id}/messages
 * - GET    /chat/unread-summary
 * - POST   /messages
 * - POST   /messages/{id}/mark-read
 * - POST   /pusher/auth
 * - POST   /conversations/{id}/mute
 * - POST   /conversations/{id}/leave
 */
class Phase10ChatTest extends MobileIntegrationTest
{
    public function test_get_conversations_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->getJson('/api/v1/conversations');

        $response->assertOk();
        $this->assertEnvelope($response);
    }

    public function test_get_conversation_show_returns_envelope(): void
    {
        $alice = $this->actingAsRole('player');
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        $response = $this->getJson("/api/v1/conversations/{$convo->id}");

        $response->assertOk();
        $this->assertEnvelope($response);
    }

    public function test_get_conversation_messages_returns_envelope(): void
    {
        $alice = $this->actingAsRole('player');
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        $response = $this->getJson("/api/v1/conversations/{$convo->id}/messages");

        $response->assertOk();
        $this->assertEnvelope($response);
    }

    public function test_get_chat_unread_summary_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->getJson('/api/v1/chat/unread-summary');

        $response->assertOk();
        $this->assertEnvelope($response);
    }

    // ============================================================
    // Sprint 7 — five new mutating / auth endpoints
    // ============================================================

    public function test_post_messages_returns_envelope(): void
    {
        $alice = $this->actingAsRole('player');
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        $response = $this->postJson('/api/v1/messages', [
            'conversation_id' => $convo->id,
            'body' => 'hi',
            'idempotency_key' => 'env-msg-12345678',
        ]);

        $response->assertCreated();
        $this->assertEnvelope($response);
    }

    public function test_post_messages_mark_read_returns_envelope(): void
    {
        $alice = $this->actingAsRole('player');
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();
        $message = Message::factory()->create([
            'conversation_id' => $convo->id,
            'user_id' => $bob->id,
        ]);

        $response = $this->postJson("/api/v1/messages/{$message->id}/mark-read");

        $response->assertOk();
        $this->assertEnvelope($response);
    }

    public function test_post_pusher_auth_returns_envelope(): void
    {
        // Validation 422 for empty body still goes through the
        // canonical envelope handler.
        $this->actingAsRole('player');

        $response = $this->postJson('/api/v1/pusher/auth', []);

        $this->assertErrorEnvelope($response, 422);
    }

    public function test_post_conversation_mute_returns_envelope(): void
    {
        $alice = $this->actingAsRole('player');
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        $response = $this->postJson("/api/v1/conversations/{$convo->id}/mute");

        $response->assertOk();
        $this->assertEnvelope($response);
    }

    public function test_post_conversation_leave_returns_envelope(): void
    {
        $alice = $this->actingAsRole('player');
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        $response = $this->postJson("/api/v1/conversations/{$convo->id}/leave");

        $response->assertOk();
        $this->assertEnvelope($response);
    }
}
