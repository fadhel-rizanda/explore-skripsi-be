<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class TestBroadcast implements ShouldBroadcast
{
    public function broadcastOn()
    {
        return [new Channel('room.testchannel')];
    }

    public function broadcastAs()
    {
        return 'test.event';
    }

    public function broadcastWith()
    {
        return [
            'text' => 'Hello from tinker!',
            'timestamp' => now()->toIso8601String(),
            'random' => rand(1000, 9999),
        ];
    }
}
