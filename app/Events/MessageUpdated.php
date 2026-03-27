<?php

namespace App\Events;

use App\Enums\ChannelEnum;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public array $data,
        public string $chatId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(ChannelEnum::CHAT->channel($this->chatId)),
        ];
    }

    public function broadcastAs(): string
    {
        return ChannelEnum::CHAT->event();
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'message.updated',
            'data' => [
                'id' => $this->data['id'],
                'chat_id' => $this->chatId,
                'title' => 'Message Updated',
                'message' => $this->data['sender']['name'] . ' has updated a message',
                'sender' => $this->data['sender'],
                'content' => $this->data['content'],
                'updated_at' => now()->toISOString(),
            ],
        ];
    }
}
