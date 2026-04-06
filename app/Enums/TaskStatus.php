<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Pending = 'pending';
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Dismissed = 'dismissed';
    case Snoozed = 'snoozed';
    case OnHold = 'on_hold';
}
