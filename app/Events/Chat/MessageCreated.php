<?php

namespace App\Events\Chat;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $channelName = $this->stripPrivatePrefix(
            $this->message->conversation?->channelName() ?? "private-conversation-{$this->message->conversation_id}",
        );

        return [new PrivateChannel($channelName)];
    }

    public function broadcastAs(): string
    {
        return 'message.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $msg = $this->message;

        return [
            'id' => $msg->id,
            'conversation_id' => $msg->conversation_id,
            'channel_id' => $msg->conversation_id,
            'sender' => ['id' => $msg->user_id],
            'body' => $msg->body,
            'content' => $msg->body,
            'type' => $msg->type,
            'attachment_url' => $msg->attachment_url,
            'created_at' => $msg->created_at?->toIso8601String(),
        ];
    }

    private function stripPrivatePrefix(string $channelName): string
    {
        return str_starts_with($channelName, 'private-')
            ? substr($channelName, strlen('private-'))
            : $channelName;
    }
}
