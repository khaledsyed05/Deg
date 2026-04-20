<?php

namespace App\Enums;

enum AccountStatus: string
{
    case Active = 'active';
    case Blocked = 'blocked';
    case Suspended = 'suspended';
    case PendingProfileCompletion = 'pending_profile_completion';
}
