<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

/**
 * Privacy contract: admins do NOT have blanket access to chat
 * content. Only active participants in a conversation can read,
 * send, mute, or leave it.
 */
class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->hasParticipant($user);
    }

    public function send(User $user, Conversation $conversation): bool
    {
        return $conversation->hasParticipant($user);
    }

    public function mute(User $user, Conversation $conversation): bool
    {
        return $conversation->hasParticipant($user);
    }

    public function leave(User $user, Conversation $conversation): bool
    {
        return $conversation->hasParticipant($user);
    }
}
