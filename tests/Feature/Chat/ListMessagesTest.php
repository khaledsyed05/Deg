<?php

namespace Tests\Feature\Chat;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ListMessagesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_messages_oldest_first_within_page(): void
    {
        $alice = $this->actingAsPlayer();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        $messages = collect();
        for ($i = 0; $i < 5; $i++) {
            $messages->push(Message::factory()->create([
                'conversation_id' => $convo->id,
                'user_id' => $i % 2 ? $alice->id : $bob->id,
            ]));
        }

        $response = $this->getJson("/api/v1/conversations/{$convo->id}/messages");

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();
        $sortedIds = $messages->pluck('id')->sort()->values()->all();

        $this->assertSame($sortedIds, $ids, 'Messages should arrive oldest-first within a page');
    }

    public function test_default_limit_is_50(): void
    {
        $alice = $this->actingAsPlayer();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        Message::factory()->count(75)->create([
            'conversation_id' => $convo->id,
            'user_id' => $bob->id,
        ]);

        $response = $this->getJson("/api/v1/conversations/{$convo->id}/messages");

        $response->assertOk();
        $this->assertCount(50, $response->json('data'));
    }

    public function test_limit_clamped_to_max_100(): void
    {
        $alice = $this->actingAsPlayer();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        Message::factory()->count(150)->create([
            'conversation_id' => $convo->id,
            'user_id' => $bob->id,
        ]);

        $response = $this->getJson("/api/v1/conversations/{$convo->id}/messages?limit=500");

        $response->assertOk();
        $this->assertLessThanOrEqual(100, count($response->json('data')));
    }

    public function test_before_id_cursor_pagination(): void
    {
        $alice = $this->actingAsPlayer();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        $messages = collect();
        for ($i = 0; $i < 10; $i++) {
            $messages->push(Message::factory()->create([
                'conversation_id' => $convo->id,
                'user_id' => $bob->id,
            ]));
        }

        $cursor = $messages[5]->id;

        $response = $this->getJson("/api/v1/conversations/{$convo->id}/messages?before_id={$cursor}");

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();

        foreach ($ids as $id) {
            $this->assertLessThan($cursor, $id);
        }
    }

    public function test_non_participant_returns_403(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();
        Message::factory()->create(['conversation_id' => $convo->id, 'user_id' => $alice->id]);

        $charlie = $this->actingAsPlayer();

        $this->getJson("/api/v1/conversations/{$convo->id}/messages")
            ->assertForbidden();
    }

    public function test_unauthenticated_returns_401(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        $this->getJson("/api/v1/conversations/{$convo->id}/messages")
            ->assertUnauthorized();
    }
}
