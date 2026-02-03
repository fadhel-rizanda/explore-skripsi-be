<?php

use App\Enums\ChannelEnum;
use App\Models\Adoption;
use Illuminate\Support\Facades\Broadcast;

// php artisan install:broadcasting
Broadcast::channel(ChannelEnum::CHAT->pattern('roomId'), function ($user, $roomId) {
    return $user->chatRooms()
        ->where('mt_chat.id', $roomId)
        ->exists();
});

Broadcast::channel(ChannelEnum::NOTIFICATION->pattern('userId'), function ($user, $userId) {
    return (string) $user->id === (string) $userId;
});

Broadcast::channel(ChannelEnum::ADOPTION->pattern('adoptionId'), function ($user, $adoptionId) {
    $adoption = Adoption::find($adoptionId);
    if (! $adoption) {
        return false;
    }

    return $user->id === $adoption->adopter_id || $user->id === $adoption->provider->id;
});

Broadcast::channel(ChannelEnum::COMMUNITY->pattern('communityId'), function ($user, $communityId) {
    return $user->communities()
        ->where('mt_community.id', $communityId)
        ->exists();
});
