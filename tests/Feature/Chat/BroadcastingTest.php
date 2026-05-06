<?php

namespace Tests\Feature\Chat;

use App\Events\Chat\MemberJoined;
use App\Events\Chat\MemberLeft;
use App\Events\Chat\MessageCreated;
use App\Events\Chat\MessageRead as MessageReadEvent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Team;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Pins the wire format of every broadcast event so a refactor that
 * changes channel naming or event payload is caught immediately.
 * Mobile listeners hard-code these strings — getting them wrong
 * silently breaks real-time updates without any visible error.
 *
 * The live Pusher Debug Console smoke test (B6 of the sprint
 * prompt) is deferred per BLOCKERS.md until real Pusher creds land.
 */
class BroadcastingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_message_created_broadcasts_on_correct_dm_channel(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();
        $message = Message::factory()->create([
            'conversation_id' => $convo->id,
            'user_id' => $alice->id,
        ]);

        $event = new MessageCreated($message->load('conversation'));

        $this->assertInstanceOf(ShouldBroadcast::class, $event);
        $this->assertSame('message.created', $event->broadcastAs());

        [$lo, $hi] = $alice->id < $bob->id ? [$alice->id, $bob->id] : [$bob->id, $alice->id];
        $channels = $event->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame("private-dm-{$lo}-{$hi}", $channels[0]->name);

        $payload = $event->broadcastWith();
        $this->assertSame($message->id, $payload['id']);
        $this->assertSame($convo->id, $payload['conversation_id']);
        $this->assertSame($convo->id, $payload['channel_id']);
    }

    public function test_message_created_broadcasts_on_correct_team_channel(): void
    {
        $captain = User::factory()->create();
        $team = Team::factory()->withCaptain($captain)->create();
        $convo = Conversation::factory()->teamChannel($team->id)->withParticipant($captain)->create();
        $message = Message::factory()->create([
            'conversation_id' => $convo->id,
            'user_id' => $captain->id,
        ]);

        $event = new MessageCreated($message->load('conversation'));

        $this->assertSame("private-team-{$team->id}", $event->broadcastOn()[0]->name);
    }

    public function test_message_read_event(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();
        $message = Message::factory()->create([
            'conversation_id' => $convo->id,
            'user_id' => $bob->id,
        ]);

        $event = new MessageReadEvent(
            $message->load('conversation'),
            $alice->id,
            '2026-05-06T12:00:00+00:00',
        );

        $this->assertInstanceOf(ShouldBroadcast::class, $event);
        $this->assertSame('message.read', $event->broadcastAs());

        $payload = $event->broadcastWith();
        $this->assertSame($message->id, $payload['message_id']);
        $this->assertSame($alice->id, $payload['user_id']);
        $this->assertSame('2026-05-06T12:00:00+00:00', $payload['read_at']);
    }

    public function test_member_joined_event(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        $event = new MemberJoined($convo->fresh(), $alice->id);

        $this->assertInstanceOf(ShouldBroadcast::class, $event);
        $this->assertSame('member.joined', $event->broadcastAs());
        $this->assertSame(['conversation_id' => $convo->id, 'user_id' => $alice->id], $event->broadcastWith());
    }

    public function test_member_left_event(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $convo = Conversation::factory()->dm($alice, $bob)->create();

        $event = new MemberLeft($convo->fresh(), $alice->id);

        $this->assertInstanceOf(ShouldBroadcast::class, $event);
        $this->assertSame('member.left', $event->broadcastAs());
        $this->assertSame(['conversation_id' => $convo->id, 'user_id' => $alice->id], $event->broadcastWith());
    }
}
