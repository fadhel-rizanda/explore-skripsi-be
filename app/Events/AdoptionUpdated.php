<?php

namespace App\Events;

use App\Enums\ChannelEnum;
use App\Models\Notification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdoptionUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notification;
    public $adoptionId;

    /**
     * Create a new event instance.
     */
    public function __construct(
        Notification $notification,
        string $adoptionId
    ) {
        $this->notification = $notification;
        $this->adoptionId = $adoptionId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(ChannelEnum::ADOPTION->channel($this->adoptionId)),
        ];
    }

    public function broadcastAs(): string
    {
        return ChannelEnum::ADOPTION->event();
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'adoption.updated',
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
