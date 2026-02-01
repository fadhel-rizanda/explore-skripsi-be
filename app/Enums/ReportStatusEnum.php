<?php

namespace App\Enums;

enum ReportStatusEnum: string
{
    case ACTIVE = 'Active';
    case RESOLVED = 'Resolved';
    case CLOSED = 'Closed';
    case IN_PROGRESS = 'In Progress';
}
