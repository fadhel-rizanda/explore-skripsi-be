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

    public $notification;

    /**
     * Create a new event instance.
     */
    public function __construct(
        Notification $notification,
    ) {
        $this->notification = $notification;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(ChannelEnum::COMMUNITY->channel($this->notification->reference_id)),
        ];
    }

    public function broadcastAs(): string
    {
        return ChannelEnum::COMMUNITY->event();
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'reference_id' => $this->notification->reference_id,
            'reference_by' => $this->notification->reference_by,
            'title' => $this->notification->title,
            'message' => $this->notification->message,
            'created_at' => $this->notification->created_at->toDateTimeString(),
        ];
    }
}
