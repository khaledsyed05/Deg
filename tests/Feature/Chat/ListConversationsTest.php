<?php

namespace Tests\Feature\Chat;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageRead;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ListConversationsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_only_caller_conversations(): void
    {
        $alice = $this->actingAsPlayer();
        $bob = User::factory()->create();
        $charlie = User::factory()->create();

        // Alice ↔ Bob (alice's)
        Conversation::factory()->dm($alice, $bob)->create();
        // Bob ↔ Charlie (NOT alice's)
        Conversation::factory()->dm($bob, $charlie)->create();

        $response = $this->getJson('/api/v1/conversations');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_returns_envelope_with_pagination_meta(): void
    {
        $this->actingAsPlayer();

        $response = $this->getJson('/api/v1/conversations');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);
    }

    public function test_unread_count_is_correct(): void
    {
        $alice = $this->actingAsPlayer();
        $bob = User::factory()->create();

        $convo = Conversation::factory()->dm($alice, $bob)->create();
        Message::factory()->count(3)->create([
            'conversation_id' => $convo->id,
            'user_id' => $bob->id,
        ]);

        $response = $this->getJson('/api/v1/conversations');

        $response->assertOk()->assertJsonPath('data.0.unread_count', 3);
    }

    public function test_unread_count_excludes_read_messages(): void
    {
        $alice = $this->actingAsPlayer();
        $bob = User::factory()->create();

        $convo = Conversation::factory()->dm($alice, $bob)->create();
        $messages = Message::factory()->count(3)->create([
            'conversation_id' => $convo->id,
            'user_id' => $bob->id,
        ]);

        // Alice reads two of them.
        foreach ($messages->take(2) as $m) {
            MessageRead::create(['message_id' => $m->id, 'user_id' => $alice->id, 'read_at' => now()]);
        }

        $response = $this->getJson('/api/v1/conversations');

        $response->assertOk()->assertJsonPath('data.0.unread_count', 1);
    }

    public function test_empty_state(): void
    {
        $this->actingAsPlayer();

        $response = $this->getJson('/api/v1/conversations');

        $response->assertOk()->assertJsonPath('data', []);
    }

    public function test_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/v1/conversations')->assertUnauthorized();
    }
}
