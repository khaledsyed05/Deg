<?php

namespace App\Services\Chat;

use App\Events\Chat\MessageRead as MessageReadEvent;
use App\Models\Message;
use App\Models\MessageRead;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MarkReadService
{
    public function markRead(Message $message, User $user): MessageRead
    {
        if ($message->user_id === $user->id) {
            throw new HttpException(422, 'Cannot mark your own message as read.');
        }

        $read = MessageRead::firstOrCreate(
            ['message_id' => $message->id, 'user_id' => $user->id],
            ['read_at' => now()],
        );

        // Fire only on the first creation to avoid spamming Pusher
        // when mobile retries.
        if ($read->wasRecentlyCreated) {
            event(new MessageReadEvent(
                $message->load('conversation'),
                $user->id,
                $read->read_at->toIso8601String(),
            ));
        }

        return $read;
    }
}
