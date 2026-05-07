<?php

namespace Tests\Feature\Chat;

use App\Events\Chat\MessageRead as MessageReadEvent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageRead;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MarkReadTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_participant_can_mark_read(): void
    {
        Event::fake();

        $alice = $this->actingAsPlayer();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();
        $message = Message::factory()->create([
            'conversation_id' => $convo->id,
            'user_id' => $bob->id,
        ]);

        $response = $this->postJson("/api/v1/messages/{$message->id}/mark-read");

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('message_reads', [
            'message_id' => $message->id,
            'user_id' => $alice->id,
        ]);

        Event::assertDispatched(MessageReadEvent::class, function (MessageReadEvent $e) use ($message, $alice) {
            return $e->message->id === $message->id && $e->userId === $alice->id;
        });
    }

    public function test_sender_cannot_mark_own_message_read(): void
    {
        $alice = $this->actingAsPlayer();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();
        $message = Message::factory()->create([
            'conversation_id' => $convo->id,
            'user_id' => $alice->id,
        ]);

        $this->postJson("/api/v1/messages/{$message->id}/mark-read")
            ->assertStatus(422);
    }

    public function test_non_participant_returns_403(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();
        $message = Message::factory()->create([
            'conversation_id' => $convo->id,
            'user_id' => $alice->id,
        ]);

        $charlie = $this->actingAsPlayer();

        $this->postJson("/api/v1/messages/{$message->id}/mark-read")
            ->assertForbidden();
    }

    public function test_already_read_is_idempotent_no_extra_event(): void
    {
        Event::fake();

        $alice = $this->actingAsPlayer();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();
        $message = Message::factory()->create([
            'conversation_id' => $convo->id,
            'user_id' => $bob->id,
        ]);

        $first = $this->postJson("/api/v1/messages/{$message->id}/mark-read");
        $second = $this->postJson("/api/v1/messages/{$message->id}/mark-read");

        $first->assertOk();
        $second->assertOk();

        $this->assertSame(
            1,
            MessageRead::where(['message_id' => $message->id, 'user_id' => $alice->id])->count(),
        );

        Event::assertDispatchedTimes(MessageReadEvent::class, 1);
    }

    public function test_unknown_message_returns_404(): void
    {
        $this->actingAsPlayer();

        $this->postJson('/api/v1/messages/999999/mark-read')->assertNotFound();
    }

    public function test_unauthenticated_returns_401(): void
    {
        $this->postJson('/api/v1/messages/1/mark-read')->assertUnauthorized();
    }
}
