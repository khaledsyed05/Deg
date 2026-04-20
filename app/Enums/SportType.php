<?php

namespace App\Enums;

enum SportType: string
{
    case Football = 'football';
    case Basketball = 'basketball';
    case Tennis = 'tennis';
    case Padel = 'padel';
    case Volleyball = 'volleyball';
    case Other = 'other';
}
