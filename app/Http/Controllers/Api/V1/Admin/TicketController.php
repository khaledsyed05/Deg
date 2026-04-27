<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\AuditLog;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = SupportTicket::query()->with(['user:id,name,phone_number']);

        if ($status = $request->string('status')->value()) {
            $query->where('status', $status);
        }

        if ($category = $request->string('category')->value()) {
            $query->where('category', $category);
        }

        $tickets = $query->latest('last_activity_at')->paginate(20);

        return $this->success([
            'data' => $tickets->items(),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'total' => $tickets->total(),
            ],
        ]);
    }

    public function assign(int $id, Request $request): JsonResponse
    {
        $data = $request->validate([
            'agent_id' => 'required|integer|exists:users,id',
        ]);

        $ticket = SupportTicket::findOrFail($id);
        $ticket->update([
            'assigned_to' => $data['agent_id'],
            'status' => 'in_progress',
        ]);

        AuditLog::record('ticket.assigned', $request->user()->id, $ticket, ['agent_id' => $data['agent_id']], $request->ip());

        return $this->success(['ticket_id' => $ticket->id, 'assigned_to' => $data['agent_id']], 'تم تعيين الوكيل');
    }

    public function reply(int $id, Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => 'required|string|min:1|max:5000',
            'close_ticket' => 'sometimes|boolean',
        ]);

        $ticket = SupportTicket::findOrFail($id);

        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $data['message'],
            'is_from_agent' => true,
        ]);

        $ticket->update([
            'status' => ! empty($data['close_ticket']) ? 'resolved' : 'awaiting_user_reply',
            'last_activity_at' => now(),
            'resolved_at' => ! empty($data['close_ticket']) ? now() : null,
        ]);

        AuditLog::record('ticket.replied', $request->user()->id, $ticket, ['message_id' => $message->id], $request->ip());

        return $this->success([
            'ticket_id' => $ticket->id,
            'message_id' => $message->id,
            'status' => $ticket->status,
        ], 'تم إرسال الرد');
    }
}
