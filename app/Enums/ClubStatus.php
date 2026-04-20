<?php

namespace App\Enums;

enum ClubStatus: string
{
    case PendingApproval = 'pending_approval';
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';
    case Rejected = 'rejected';
}
