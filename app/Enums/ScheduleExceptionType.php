<?php

namespace App\Enums;

enum ScheduleExceptionType: string
{
    case CLOSED = 'closed';
    case SPECIAL_HOURS = 'special_hours';
}
