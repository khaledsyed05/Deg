<?php

namespace Tests\Feature\Chat;

use App\Events\Chat\MessageCreated;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SendMessageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_participant_can_send_message_and_event_is_dispatched(): void
    {
        Event::fake();

        $alice = $this->actingAsPlayer();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        $response = $this->postJson('/api/v1/messages', [
            'conversation_id' => $convo->id,
            'body' => 'hello',
            'idempotency_key' => 'msg-test-12345678',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'conversation_id', 'body', 'type', 'created_at'],
            ])
            ->assertJsonPath('data.body', 'hello')
            ->assertJsonPath('data.conversation_id', $convo->id);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $convo->id,
            'user_id' => $alice->id,
            'body' => 'hello',
        ]);

        Event::assertDispatched(MessageCreated::class, function (MessageCreated $e) use ($convo) {
            return $e->message->conversation_id === $convo->id;
        });
    }

    public function test_idempotent_retry_returns_same_message(): void
    {
        $alice = $this->actingAsPlayer();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        $payload = [
            'conversation_id' => $convo->id,
            'body' => 'first',
            'idempotency_key' => 'msg-idem-12345678',
        ];

        $first = $this->postJson('/api/v1/messages', $payload);
        $second = $this->postJson('/api/v1/messages', array_merge($payload, ['body' => 'second-but-ignored']));

        $first->assertCreated();
        $second->assertCreated();

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame(1, Message::where('conversation_id', $convo->id)->count());
    }

    public function test_non_participant_cannot_send(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        $charlie = $this->actingAsPlayer();

        $this->postJson('/api/v1/messages', [
            'conversation_id' => $convo->id,
            'body' => 'i should not be here',
            'idempotency_key' => 'msg-evil-12345678',
        ])->assertForbidden();
    }

    public function test_missing_idempotency_key_returns_422(): void
    {
        $alice = $this->actingAsPlayer();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        $this->postJson('/api/v1/messages', [
            'conversation_id' => $convo->id,
            'body' => 'no key',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['idempotency_key']);
    }

    public function test_body_too_long_returns_422(): void
    {
        $alice = $this->actingAsPlayer();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        $this->postJson('/api/v1/messages', [
            'conversation_id' => $convo->id,
            'body' => str_repeat('a', 5001),
            'idempotency_key' => 'msg-toolong-12345678',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['body']);
    }

    public function test_unknown_conversation_returns_422(): void
    {
        $this->actingAsPlayer();

        $this->postJson('/api/v1/messages', [
            'conversation_id' => 999999,
            'body' => 'no such conv',
            'idempotency_key' => 'msg-noconv-12345678',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['conversation_id']);
    }

    public function test_unauthenticated_returns_401(): void
    {
        $this->postJson('/api/v1/messages', [
            'conversation_id' => 1,
            'body' => 'x',
            'idempotency_key' => 'msg-anon-12345678',
        ])->assertUnauthorized();
    }
}
