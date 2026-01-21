<?php

namespace App\Enums;

enum ReportReferenceEnum: string
{
    case USER = 'user';
    case POST = 'post';
    case COMMUNITY = 'community';
    case PET = 'pet';
    case ADOPTION = 'adoption';
    case REPORT = 'report';

    public static function allValues(): array
    {
        return [
            self::USER->value,
            self::PET->value,
            self::COMMUNITY->value,
            self::POST->value,
            self::ADOPTION->value,
        ];
    }
}
