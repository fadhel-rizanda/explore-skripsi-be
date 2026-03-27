<?php

namespace App\Events;

use App\Enums\ChannelEnum;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeleted implements ShouldBroadcast
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
            'type' => 'message.deleted',
            'data' => [
                'id' => $this->data['id'],
                'chat_id' => $this->chatId,
                'title' => 'Message Deleted',
                'sender' => $this->data['sender'],
                'message' => $this->data['sender']['name'] . ' has deleted a message',
            ],
        ];
    }
}
