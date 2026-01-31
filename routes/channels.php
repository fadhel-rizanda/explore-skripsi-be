<?php

use Illuminate\Support\Facades\Broadcast;

// php artisan install:broadcasting
Broadcast::channel('chat.{roomId}', function ($user, $roomId) {
    return $user->chatRooms()
        ->where('mt_chat.id', $roomId)
        ->exists();
});

// TODO: update logic relasi di model user
Broadcast::channel('notification.{userId}', function ($user, $userId) {
    return $user->notifications()
        ->where('tr_notification.user_id', $userId)
        ->exists();
});

Broadcast::channel('adoption.{adoptionId}', function ($user, $adoptionId) {
    return $user->adoptions()
        ->where('mt_adoption_application.id', $adoptionId)
        ->exists();
});

Broadcast::channel('community.{communityId}', function ($user, $communityId) {
    return $user->communities()
        ->where('tr_follow_community.user_id', $communityId)
        ->exists();
});
