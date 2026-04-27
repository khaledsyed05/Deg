<?php

namespace App\Services\Support;

use App\Exceptions\Support\TicketException;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TicketService
{
    public function getTicketDetails(int $ticketId, User $user): array
    {
        $ticket = SupportTicket::with(['messages.sender', 'assignedAgent'])
            ->where('id', $ticketId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return [
            'ticket' => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'subject' => $ticket->subject,
                'category' => $ticket->category,
                'priority' => $ticket->priority,
                'status' => $ticket->status,
                'created_at' => $ticket->created_at?->toIso8601String(),
                'last_activity_at' => $ticket->last_activity_at?->toIso8601String(),
                'assigned_agent' => $ticket->assignedAgent ? [
                    'id' => $ticket->assignedAgent->id,
                    'name' => $ticket->assignedAgent->name,
                ] : null,
                'estimated_resolution_hours' => $this->estimateResolutionTime($ticket),
            ],
            'messages' => $ticket->messages
                ->where('is_internal_note', false)
                ->map(fn ($m) => [
                    'id' => $m->id,
                    'sender_type' => $m->sender_type,
                    'sender_name' => $m->sender?->name ?? 'النظام',
                    'message' => $m->message,
                    'attachments' => $m->attachments ?? [],
                    'created_at' => $m->created_at?->toIso8601String(),
                ])->values()->all(),
            'status_history' => $ticket->status_history ?? [],
        ];
    }

    public function replyToTicket(int $ticketId, User $user, string $message, array $attachments = []): TicketMessage
    {
        $ticket = SupportTicket::where('id', $ticketId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if (in_array($ticket->status, ['closed', 'resolved'], true)) {
            throw new TicketException('Cannot reply to closed/resolved ticket', 422);
        }

        return DB::transaction(function () use ($ticket, $user, $message, $attachments) {
            $msg = TicketMessage::create([
                'ticket_id' => $ticket->id,
                'sender_type' => 'user',
                'sender_id' => $user->id,
                'message' => $message,
                'attachments' => $attachments ?: null,
            ]);

            $ticket->update([
                'status' => $ticket->status === 'awaiting_user_reply' ? 'in_progress' : 'awaiting_agent_reply',
                'last_activity_at' => now(),
            ]);

            return $msg;
        });
    }

    private function estimateResolutionTime(SupportTicket $ticket): int
    {
        return match ($ticket->priority) {
            'urgent' => 2,
            'high' => 8,
            'medium' => 24,
            'low' => 48,
            default => 24,
        };
    }
}
