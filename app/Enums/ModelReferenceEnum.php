<?php

namespace App\Enums;

enum ModelReferenceEnum: string
{
    case USER = 'User';
    case POST = 'Post';
    case COMMUNITY = 'Community';
    case PET = 'Pet';
    case ADOPTION = 'Adoption';
    case REPORT = 'Report';
    case CHAT = 'Chat';
    case REQUIREMENT = 'Requirement';
    case HANDOVER = 'Handover';

    public static function allValues(): array
    {
        return [
            self::USER->value,
            self::POST->value,
            self::COMMUNITY->value,
            self::PET->value,
            self::ADOPTION->value,
            self::REPORT->value,
            self::CHAT->value,
            self::REQUIREMENT->value,
            self::HANDOVER->value,
        ];
    }
}
