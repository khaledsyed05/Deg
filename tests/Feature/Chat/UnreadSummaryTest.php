<?php

namespace Tests\Feature\Chat;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UnreadSummaryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_total_and_per_channel_counts(): void
    {
        $alice = $this->actingAsPlayer();
        $bob = User::factory()->create();
        $charlie = User::factory()->create();

        $convo1 = Conversation::factory()->dm($alice, $bob)->create();
        $convo2 = Conversation::factory()->dm($alice, $charlie)->create();

        Message::factory()->count(3)->create([
            'conversation_id' => $convo1->id,
            'user_id' => $bob->id,
        ]);
        Message::factory()->count(2)->create([
            'conversation_id' => $convo2->id,
            'user_id' => $charlie->id,
        ]);

        $response = $this->getJson('/api/v1/chat/unread-summary');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total_unread',
                    'by_channel' => [
                        '*' => ['conversation_id', 'channel_id', 'unread_count'],
                    ],
                ],
            ])
            ->assertJsonPath('data.total_unread', 5);

        $byChannel = collect($response->json('data.by_channel'));
        $this->assertSame(3, $byChannel->firstWhere('conversation_id', $convo1->id)['unread_count']);
        $this->assertSame(2, $byChannel->firstWhere('conversation_id', $convo2->id)['unread_count']);
    }

    public function test_excludes_caller_own_messages_from_unread(): void
    {
        $alice = $this->actingAsPlayer();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        // Alice sent 5 messages — these don't count as unread for her.
        Message::factory()->count(5)->create([
            'conversation_id' => $convo->id,
            'user_id' => $alice->id,
        ]);

        $response = $this->getJson('/api/v1/chat/unread-summary');

        $response->assertOk()
            ->assertJsonPath('data.total_unread', 0)
            ->assertJsonPath('data.by_channel', []);
    }

    public function test_empty_state(): void
    {
        $this->actingAsPlayer();

        $response = $this->getJson('/api/v1/chat/unread-summary');

        $response->assertOk()
            ->assertJsonPath('data.total_unread', 0)
            ->assertJsonPath('data.by_channel', []);
    }

    public function test_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/v1/chat/unread-summary')->assertUnauthorized();
    }
}
