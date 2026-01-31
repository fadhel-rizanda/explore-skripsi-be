<?php

namespace App\Enums;

enum AdoptionStatusEnum: string
{
    case PENDING = 'Pending';
    case NEED_AN_ACTION = 'Need an Action';
    case IN_PROGRESS = 'In Progress';
    case COMPLETED = 'Completed';
    case REJECTED = 'Rejected';
}
