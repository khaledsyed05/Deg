<?php

namespace Tests\Feature\Phase15;

use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\User;
use Tests\TestCase;

class TicketReplyTest extends TestCase
{
    private function makeTicket(int $userId, string $status = 'open'): SupportTicket
    {
        return SupportTicket::create([
            'user_id' => $userId,
            'subject' => 'Test',
            'category' => 'general',
            'priority' => 'medium',
            'status' => $status,
            'description' => 'Test ticket',
        ]);
    }

    public function test_show_returns_ticket_with_messages(): void
    {
        $user = $this->actingAsPlayer();
        $ticket = $this->makeTicket($user->id);
        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_type' => 'user',
            'sender_id' => $user->id,
            'message' => 'hello',
        ]);

        $this->getJson("/api/v1/support/tickets/{$ticket->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.ticket.id', $ticket->id)
            ->assertJsonCount(1, 'data.messages');
    }

    public function test_cannot_view_other_users_ticket(): void
    {
        $other = User::factory()->create();
        $ticket = $this->makeTicket($other->id);
        $this->actingAsPlayer();

        $this->getJson("/api/v1/support/tickets/{$ticket->id}")
            ->assertStatus(404);
    }

    public function test_user_can_reply_to_open_ticket(): void
    {
        $user = $this->actingAsPlayer();
        $ticket = $this->makeTicket($user->id, 'open');

        $this->postJson("/api/v1/support/tickets/{$ticket->id}/reply", [
            'message' => 'follow up',
        ])->assertStatus(201)
            ->assertJsonPath('data.ticket_status', 'awaiting_agent_reply');

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'sender_id' => $user->id,
        ]);
    }

    public function test_cannot_reply_to_closed_ticket(): void
    {
        $user = $this->actingAsPlayer();
        $ticket = $this->makeTicket($user->id, 'closed');

        $this->postJson("/api/v1/support/tickets/{$ticket->id}/reply", [
            'message' => 'hi',
        ])->assertStatus(422);
    }
}
