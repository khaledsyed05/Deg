<?php

namespace Tests\Feature\Chat;

use App\Events\Chat\MemberLeft;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MuteAndLeaveTest extends TestCase
{
    use LazilyRefreshDatabase;

    // ==================== mute ====================

    public function test_participant_can_mute(): void
    {
        $alice = $this->actingAsPlayer();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        $response = $this->postJson("/api/v1/conversations/{$convo->id}/mute");

        $response->assertOk()->assertJsonPath('data.muted', true);

        $this->assertNotNull(
            ConversationParticipant::where(['conversation_id' => $convo->id, 'user_id' => $alice->id])
                ->value('muted_at'),
        );
    }

    public function test_mute_is_idempotent(): void
    {
        $alice = $this->actingAsPlayer();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        $first = $this->postJson("/api/v1/conversations/{$convo->id}/mute");
        $firstAt = $first->json('data.muted_at');

        $second = $this->postJson("/api/v1/conversations/{$convo->id}/mute");

        $first->assertOk();
        $second->assertOk();
        $this->assertSame($firstAt, $second->json('data.muted_at'));
    }

    public function test_non_participant_cannot_mute(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        $charlie = $this->actingAsPlayer();

        $this->postJson("/api/v1/conversations/{$convo->id}/mute")
            ->assertForbidden();
    }

    public function test_unauthenticated_mute_returns_401(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        $this->postJson("/api/v1/conversations/{$convo->id}/mute")->assertUnauthorized();
    }

    // ==================== leave ====================

    public function test_participant_can_leave(): void
    {
        Event::fake();

        $alice = $this->actingAsPlayer();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        $response = $this->postJson("/api/v1/conversations/{$convo->id}/leave");

        $response->assertOk()->assertJsonStructure(['data' => ['conversation_id', 'left_at']]);

        $this->assertNotNull(
            ConversationParticipant::where(['conversation_id' => $convo->id, 'user_id' => $alice->id])
                ->value('left_at'),
        );

        Event::assertDispatched(MemberLeft::class, function (MemberLeft $e) use ($convo, $alice) {
            return $e->conversation->id === $convo->id && $e->userId === $alice->id;
        });
    }

    public function test_already_left_returns_403_on_second_attempt(): void
    {
        $alice = $this->actingAsPlayer();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        $this->postJson("/api/v1/conversations/{$convo->id}/leave")->assertOk();

        // After leaving, hasParticipant filters by left_at IS NULL,
        // so a second leave call comes through as a non-participant
        // — and the policy says no.
        $this->postJson("/api/v1/conversations/{$convo->id}/leave")->assertForbidden();
    }

    public function test_non_participant_cannot_leave(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        $charlie = $this->actingAsPlayer();

        $this->postJson("/api/v1/conversations/{$convo->id}/leave")
            ->assertForbidden();
    }

    public function test_last_participant_in_dm_leave_keeps_conversation_alive(): void
    {
        $alice = $this->actingAsPlayer();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        // Bob also leaves first.
        ConversationParticipant::where(['conversation_id' => $convo->id, 'user_id' => $bob->id])
            ->update(['left_at' => now()]);

        $this->postJson("/api/v1/conversations/{$convo->id}/leave")->assertOk();

        // Conversation row still exists — history is preserved per spec.
        $this->assertDatabaseHas('conversations', ['id' => $convo->id]);
    }

    public function test_unauthenticated_leave_returns_401(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        $this->postJson("/api/v1/conversations/{$convo->id}/leave")->assertUnauthorized();
    }
}
