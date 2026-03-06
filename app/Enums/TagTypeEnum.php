<?php

namespace App\Enums;

enum TagTypeEnum: string
{
    case ADOPTION_STAGE   = 'adoption.stage';
    case TYPE_OF_ANIMAL  = 'type_of_animal';
    case PET_PHYSIQUE     = 'pet.physique';
    case PET_PERSONALITY  = 'pet.personality';
    case USER_PERSONALITY = 'user.personality';
    case REQUIREMENT      = 'requirement';
    case COMMUNITY        = 'community';
    case REPORT_REASON    = 'report_reason';
}
