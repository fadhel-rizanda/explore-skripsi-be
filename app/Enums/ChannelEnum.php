<?php

namespace App\Enums;

enum ChannelEnum: string
{
    case NOTIFICATION = 'notification';
    case COMMUNITY = 'community';
    case ADOPTION = 'adoption';
    case CHAT = 'chat';

    public function event(): string
    {
        return match ($this) {
            self::NOTIFICATION => 'notification.sent',
            self::COMMUNITY => 'community.updated',
            self::ADOPTION => 'adoption.updated',
            self::CHAT => 'message.sent',
        };
    }

    public function channel(string|int $id): string
    {
        return "{$this->value}.{$id}";
    }

    public function pattern(string $param): string
    {
        return "{$this->value}.{{$param}}";
    }
}
