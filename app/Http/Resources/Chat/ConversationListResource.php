<?php

namespace App\Http\Resources\Chat;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight resource for the conversation-list view.
 *
 * @mixin Conversation
 */
class ConversationListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $unread = $user instanceof User ? $this->unreadCountFor($user) : 0;
        $latest = $this->latestMessage;

        return [
            'id' => $this->id,
            'channel_id' => $this->id,
            'channel_name' => $this->channelName(),
            'type' => $this->type,
            'reference_id' => $this->reference_id,
            'last_message' => $latest ? [
                'id' => $latest->id,
                'body' => $latest->body,
                'type' => $latest->type,
                'sender_id' => $latest->user_id,
                'created_at' => $latest->created_at?->toIso8601String(),
            ] : null,
            'unread_count' => $unread,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
