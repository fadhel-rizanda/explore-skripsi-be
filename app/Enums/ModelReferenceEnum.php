<?php

namespace App\Enums;

enum ModelReferenceEnum: string
{
    case USER = 'user';
    case POST = 'post';
    case COMMUNITY = 'community';
    case PET = 'pet';
    case ADOPTION = 'adoption';
    case REPORT = 'report';
    case CHAT = 'chat';
    case MESSAGE = 'message';
    case REQUIREMENT = 'requirement';
    case MEETNGREET = 'meetngreet';
    case HANDOVER = 'handover';

    public function modelClass(): string
    {
        return match ($this) {
            self::USER => \App\Models\User::class,
            self::POST => \App\Models\Post::class,
            self::COMMUNITY => \App\Models\Community::class,
            self::PET => \App\Models\Pet::class,
            self::ADOPTION => \App\Models\Adoption::class,
            self::REPORT => \App\Models\Report::class,
            self::CHAT => \App\Models\Chat::class,
            self::MESSAGE => \App\Models\MESSAGE::class,
            self::REQUIREMENT => \App\Models\Requirement::class,
            self::MEETNGREET => \App\Models\MeetNGreet::class,
            self::HANDOVER => \App\Models\Handover::class,
        };
    }

    public static function allValues(): array
    {
        return [
            self::USER->value,
            self::PET->value,
            self::COMMUNITY->value,
            self::POST->value,
            self::ADOPTION->value,
            self::REPORT->value,
            self::CHAT->value,
            self::MESSAGE->value,
            self::REQUIREMENT->value,
            self::MEETNGREET->value,
            self::HANDOVER->value,
        ];
    }

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

    public function resolve(string $id)
    {
        return $this->modelClass()::find($id);
    }
}
