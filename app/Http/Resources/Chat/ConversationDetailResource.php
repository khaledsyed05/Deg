<?php

namespace App\Http\Resources\Chat;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Richer detail resource — includes participant list. Used by
 * GET /conversations/{id}.
 *
 * @mixin Conversation
 */
class ConversationDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $unread = $user instanceof User ? $this->unreadCountFor($user) : 0;

        return [
            'id' => $this->id,
            'channel_id' => $this->id,
            'channel_name' => $this->channelName(),
            'type' => $this->type,
            'reference_id' => $this->reference_id,
            'participants' => $this->whenLoaded(
                'activeParticipants',
                fn () => $this->activeParticipants->map(fn ($p) => [
                    'user_id' => $p->user_id,
                    'name' => $p->user?->name,
                    'joined_at' => $p->joined_at?->toIso8601String(),
                    'muted' => $p->isMuted(),
                ])->values()->all(),
                fn () => null,
            ),
            'unread_count' => $unread,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
