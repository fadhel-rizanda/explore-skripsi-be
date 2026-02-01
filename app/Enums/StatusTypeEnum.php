<?php

namespace App\Enums;

enum StatusTypeEnum: string
{
    case ADOPTION = 'adoption';
    case PET = 'pet';
    case REPORT = 'report';
}
