<?php

use App\Enums\ChannelPrefixEnum;
use App\Models\Adoption;
use Illuminate\Support\Facades\Broadcast;

// php artisan install:broadcasting
Broadcast::channel(ChannelPrefixEnum::CHAT->value . '{roomId}', function ($user, $roomId) {
    return $user->chatRooms()
        ->where('mt_chat.id', $roomId)
        ->exists();
});

// TODO: update logic relasi di model user
Broadcast::channel(ChannelPrefixEnum::NOTIFICATION->value . '{userId}', function ($user, $userId) {
    return $user->notifications()
        ->where('tr_notification.user_id', $userId)
        ->exists();
});

Broadcast::channel(ChannelPrefixEnum::ADOPTION->value . '{adoptionId}', function ($user, $adoptionId) {
    $adoption = Adoption::find($adoptionId);
    if (! $adoption) {
        return false;
    }

    return $user->id === $adoption->adopter_id || $user->id === $adoption->provider->id;
});

Broadcast::channel(ChannelPrefixEnum::COMMUNITY->value . '{communityId}', function ($user, $communityId) {
    return $user->communities()
        ->where('tr_follow_community.user_id', $communityId)
        ->exists();
});
