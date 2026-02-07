<?php

namespace App\Enums;

enum ModelReferenceEnum: string
{
    case USER = 'user';
    case POST = 'post';
    case COMMUNITY = 'community';
    case PET = 'pet';
    case ADOPTION = 'adoption';
    case ADOPTION_HANDOVER = 'adoption.handover';
    case ADOPTION_MEETNGREET = 'adoption.meetngreet';
    case ADOPTION_REQUIREMENT = 'adoption.requirement';
    case REPORT = 'report';
    case CHAT = 'chat';

    public static function allReportValues(): array
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
