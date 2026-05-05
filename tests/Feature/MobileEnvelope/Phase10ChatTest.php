<?php

namespace Tests\Feature\MobileEnvelope;

use Tests\MobileIntegrationTest;

/**
 * Phase 10 — Chat (Pusher) — 4 generic Social endpoints
 * (full chat suite is Sprint 7 work; per BACKEND_REQUIREMENTS.md
 * coverage table all chat endpoints are gaps. The 4 testable here
 * are the generic conversation reads and the unread summary).
 *
 * - GET /conversations
 * - GET /conversations/{channelId}
 * - GET /conversations/{channelId}/messages
 * - GET /chat/unread-summary
 */
class Phase10ChatTest extends MobileIntegrationTest
{
    public function test_get_conversations_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->getJson('/api/v1/conversations');

        $this->assertEnvelope($response);
    }

    public function test_get_conversation_show_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->getJson('/api/v1/conversations/1');

        $this->assertEnvelope($response);
    }

    public function test_get_conversation_messages_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->getJson('/api/v1/conversations/1/messages');

        $this->assertEnvelope($response);
    }

    public function test_get_chat_unread_summary_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->getJson('/api/v1/chat/unread-summary');

        $this->assertEnvelope($response);
    }
}
