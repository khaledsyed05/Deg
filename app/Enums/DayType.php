<?php

namespace App\Enums;

enum DayType: string
{
    case AllDays = 'all_days';
    case Weekday = 'weekday';
    case Weekend = 'weekend';
    case Friday = 'friday';
    case SpecificDay = 'specific_day';
}
