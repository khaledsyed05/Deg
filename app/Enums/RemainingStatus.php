<?php

namespace App\Enums;

enum RemainingStatus: string
{
    case None = 'none';
    case DueOnArrival = 'due_on_arrival';
    case Confirmed = 'confirmed';
    case Waived = 'waived';
}
