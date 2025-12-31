<?php

use Illuminate\Support\Facades\Broadcast;

// php artisan install:broadcasting
Broadcast::channel('room.{roomId}', function ($user, $roomId) {
    \Log::info('Channel Auth', [
        'user' => $user->email,
        'room' => $roomId,
    ]);

    $hasAccess = $user->chatRooms()
        ->where('chat_rooms.id', $roomId)
        ->exists();

    \Log::info('Result', ['access' => $hasAccess]);

    return $hasAccess;
});
