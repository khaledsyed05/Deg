<?php

namespace App\Enums;

enum CommissionScope: string
{
    case Global = 'global';
    case Club = 'club';
    case Venue = 'venue';
}
