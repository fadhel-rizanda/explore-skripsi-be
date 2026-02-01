<?php

namespace App\Enums;

enum PetStatusEnum: string
{
    case AVAILABLE = 'Available';
    case PENDING = 'Pending';
    case ADOPTED = 'Adopted';
    case NOT_AVAILABLE = 'Not Available';
}
