<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Http\Resources\Chat\ConversationDetailResource;
use App\Http\Resources\Chat\ConversationListResource;
use App\Http\Resources\Chat\MessageResource;
use App\Http\Traits\ApiResponse;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversations = Conversation::query()
            ->whereHas('activeParticipants', fn ($q) => $q->where('user_id', $user->id))
            ->with(['latestMessage'])
            ->orderByDesc('updated_at')
            ->paginate(20);

        return $this->paginated($conversations, ConversationListResource::class);
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $conversation = Conversation::with(['activeParticipants.user'])->findOrFail($id);

        $this->authorize('view', $conversation);

        return $this->success(new ConversationDetailResource($conversation));
    }

    public function messages(int $id, Request $request): JsonResponse
    {
        $conversation = Conversation::findOrFail($id);

        $this->authorize('view', $conversation);

        $beforeId = $request->integer('before_id') ?: null;
        $limit = (int) min(max(1, $request->integer('limit') ?: 50), 100);

        $messages = Message::query()
            ->where('conversation_id', $conversation->id)
            ->when($beforeId, fn ($q, $bid) => $q->where('id', '<', $bid))
            ->with('sender')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        return $this->success(MessageResource::collection($messages)->resolve());
    }

    public function mute(int $id, Request $request): JsonResponse
    {
        return $this->error('Not yet implemented', null, 501);
    }

    public function leave(int $id, Request $request): JsonResponse
    {
        return $this->error('Not yet implemented', null, 501);
    }

    public function unreadSummary(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversations = Conversation::query()
            ->whereHas('activeParticipants', fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        $rows = [];
        $total = 0;

        foreach ($conversations as $convoId) {
            $count = Message::query()
                ->where('conversation_id', $convoId)
                ->where('user_id', '!=', $user->id)
                ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $user->id))
                ->count();

            if ($count === 0) {
                continue;
            }

            $rows[] = [
                'conversation_id' => $convoId,
                'channel_id' => $convoId,
                'unread_count' => $count,
            ];
            $total += $count;
        }

        return $this->success([
            'total_unread' => $total,
            'by_channel' => $rows,
        ]);
    }
}
