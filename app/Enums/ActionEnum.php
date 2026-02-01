<?php

namespace App\Enums;

enum ActionEnum: string
{
    case DEACTIVATED = 'deactivated';
    case ACTIVATED = 'activated';
    case TAKEDOWN = 'taken down';
    case RESTORED = 'restored';

    public static function allValues(): array
    {
        return [
            self::DEACTIVATED->value,
            self::ACTIVATED->value,
            self::TAKEDOWN->value,
            self::RESTORED->value,
        ];
    }
}
