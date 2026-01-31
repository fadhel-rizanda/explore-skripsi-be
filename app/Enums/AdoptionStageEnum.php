<?php

namespace App\Enums;

enum AdoptionStageEnum: string
{
    case APPLICATION_SUBMITTED = 'Submitted';
    case REQUIREMENT = 'Requirement';
    case MEET_N_GREET = 'Meet & Greet';
    case HANDOVER = 'Handover';
    case COMPLETED = 'Completed';
    case REJECTED = 'Rejected';
}
