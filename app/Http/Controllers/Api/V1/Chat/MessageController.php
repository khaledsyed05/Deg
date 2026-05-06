<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Chat\SendMessageRequest;
use App\Http\Resources\Chat\MessageResource;
use App\Http\Traits\ApiResponse;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\Chat\MarkReadService;
use App\Services\Chat\SendMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    use ApiResponse;

    public function store(SendMessageRequest $request, SendMessageService $service): JsonResponse
    {
        $conversation = Conversation::findOrFail((int) $request->validated('conversation_id'));

        $this->authorize('send', $conversation);

        $message = $service->send($request->user(), $conversation, $request->validated());

        return $this->success(new MessageResource($message), null, 201);
    }

    public function markRead(int $id, Request $request, MarkReadService $service): JsonResponse
    {
        $message = Message::findOrFail($id);

        $conversation = $message->conversation;

        if (! $conversation || ! $conversation->hasParticipant($request->user())) {
            return $this->forbidden();
        }

        $service->markRead($message, $request->user());

        return $this->noContent();
    }
}
