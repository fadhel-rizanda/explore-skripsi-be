<?php

namespace App\Enums;

enum ChatTypeEnum: string
{
    case PRIVATE = 'private';
    case PUBLIC = 'public';

    public static function allValues(): array
    {
        return [
            self::PRIVATE->value,
            self::PUBLIC->value,
        ];
    }
}
