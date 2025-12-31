<?php

use Illuminate\Support\Facades\Broadcast;

// php artisan install:broadcasting
Broadcast::channel('room.{roomId}', function ($user, $roomId) {
    return $user->chatRooms()
        ->where('chat_rooms.id', $roomId)
        ->exists();
});
