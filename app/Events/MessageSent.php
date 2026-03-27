<?php

namespace App\Events;

use App\Enums\ChannelEnum;
use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Message $message,
    ) {
        $this->message->load(['user']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(ChannelEnum::CHAT->channel($this->message->chat_id)),
        ];
    }

    public function broadcastAs(): string
    {
        return ChannelEnum::CHAT->event();
    }

    public function broadcastWith(): array
    {
        $message = Message::with(['user.attachment', 'attachment'])->findOrFail($this->message->id);

        return [
            'type' => 'message.sent',
            'data' => [
                'id' => $message->id,
                'chat_id' => $message->chat_id,
                'content' => $message->content,
                'created_at' => $message->created_at->toISOString(),
                'updated_at' => $message->updated_at->toISOString(),
                'sender' => [
                    'id' => $message->user->id,
                    'name' => $message->user->name,
                    'email' => $message->user->email,
                    'avatar' => $message->user->attachment?->public_url ?? $message->user->avatar,
                ],
                'attachment' => $message->attachment ? [
                    'id' => $message->attachment->id,
                    'public_url' => $message->attachment->public_url,
                    'filename' => $message->attachment->filename,
                    'file_size' => $message->attachment->file_size,
                    'mime_type' => $message->attachment->mime_type,
                ] : null,
            ],
        ];
    }
}
