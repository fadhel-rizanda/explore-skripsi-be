<?php

namespace App\Enums;

enum RoleEnum: string
{
    case ADOPTER = 'adopter';
    case PROVIDER = 'provider';
    case ADMIN = 'admin';

    public static function publicRoles(): array
    {
        return [
            self::ADOPTER->value,
            self::PROVIDER->value,
        ];
    }
}
