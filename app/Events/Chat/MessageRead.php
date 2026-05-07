<?php

namespace App\Events\Chat;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message,
        public int $userId,
        public string $readAt,
    ) {}

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
        return 'message.read';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->message->id,
            'user_id' => $this->userId,
            'read_at' => $this->readAt,
        ];
    }

    private function stripPrivatePrefix(string $channelName): string
    {
        return str_starts_with($channelName, 'private-')
            ? substr($channelName, strlen('private-'))
            : $channelName;
    }
}
