<?php

namespace App\Events;

use App\Enums\ChannelEnum;
use App\Models\Notification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommunityUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Notification $notification,
        public string $communityId
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(ChannelEnum::COMMUNITY->channel($this->communityId)),
        ];
    }

    public function broadcastAs(): string
    {
        return ChannelEnum::COMMUNITY->event();
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'community.updated',
            'data' => [
                'id' => $this->notification->id,
                'reference_id' => $this->notification->reference_id,
                'reference_type' => $this->notification->reference_type,
                'title' => $this->notification->title,
                'message' => $this->notification->message,
                'created_at' => $this->notification->created_at->toDateTimeString(),
            ],
        ];
    }
}
