<?php

namespace App\Enums;

enum UserRole: string
{
    case Player = 'player';
    case VenueManager = 'venue_manager';
    case Admin = 'admin';
}
