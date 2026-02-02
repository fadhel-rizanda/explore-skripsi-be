<?php

namespace App\Enums;

enum ChannelPrefixEnum: string
{
    case CHAT = 'chat.';
    case NOTIFICATION = 'notification.';
    case ADOPTION = 'adoption.';
    case COMMUNITY = 'community.';
}
