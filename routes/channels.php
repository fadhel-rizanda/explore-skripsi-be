<?php

use Illuminate\Support\Facades\Broadcast;

// php artisan install:broadcasting
Broadcast::channel('chat.{roomId}', function ($user, $roomId) {
    return $user->chatRooms()
        ->where('mt_chat.id', $roomId)
        ->exists();
});
