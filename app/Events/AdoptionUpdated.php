<?php

namespace App\Events;

use App\Enums\ChannelPrefixEnum;
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
            new PrivateChannel(ChannelPrefixEnum::ADOPTION->value . $this->notification->reference_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'adoption.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'adoption_id' => $this->notification->reference_id,
        ];
    }
}
