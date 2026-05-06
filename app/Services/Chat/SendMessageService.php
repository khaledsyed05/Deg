<?php

namespace App\Services\Chat;

use App\Events\Chat\MessageCreated;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Support\MessageIdempotency;
use Illuminate\Support\Facades\DB;

class SendMessageService
{
    /**
     * @param  array<string, mixed>  $data  validated input from SendMessageRequest
     */
    public function send(User $sender, Conversation $conversation, array $data): Message
    {
        $key = (string) $data['idempotency_key'];

        $message = MessageIdempotency::run($key, function () use ($sender, $conversation, $data, $key) {
            return DB::transaction(function () use ($sender, $conversation, $data, $key) {
                $message = Message::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $sender->id,
                    'body' => $data['body'] ?? $data['content'] ?? null,
                    'type' => $data['type'] ?? 'text',
                    'attachment_url' => $data['attachment_url'] ?? null,
                    'idempotency_key' => $key,
                ]);

                $conversation->touch();

                return $message;
            });
        });

        event(new MessageCreated($message->load('conversation')));

        return $message;
    }
}
