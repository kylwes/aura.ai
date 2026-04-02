<?php

namespace App\Enums;

enum InboxItemStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Snoozed = 'snoozed';
    case Dismissed = 'dismissed';
}
