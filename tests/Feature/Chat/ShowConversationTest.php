<?php

namespace Tests\Feature\Chat;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ShowConversationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_participant_can_view_conversation(): void
    {
        $alice = $this->actingAsPlayer();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        $response = $this->getJson("/api/v1/conversations/{$convo->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'channel_id', 'channel_name', 'type', 'participants', 'unread_count'],
            ])
            ->assertJsonPath('data.id', $convo->id)
            ->assertJsonPath('data.type', 'dm');

        $this->assertCount(2, $response->json('data.participants'));
    }

    public function test_non_participant_cannot_view_conversation(): void
    {
        $charlie = $this->actingAsPlayer();
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        $this->getJson("/api/v1/conversations/{$convo->id}")
            ->assertForbidden();
    }

    public function test_deleted_conversation_returns_404(): void
    {
        $alice = $this->actingAsPlayer();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();
        $convo->delete();

        $this->getJson("/api/v1/conversations/{$convo->id}")
            ->assertNotFound();
    }

    public function test_unknown_conversation_returns_404(): void
    {
        $this->actingAsPlayer();

        $this->getJson('/api/v1/conversations/999999')->assertNotFound();
    }

    public function test_unauthenticated_returns_401(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        $this->getJson("/api/v1/conversations/{$convo->id}")->assertUnauthorized();
    }
}
