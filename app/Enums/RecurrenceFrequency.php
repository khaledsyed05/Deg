<?php

namespace App\Enums;

enum RecurrenceFrequency: string
{
    case Weekly = 'weekly';
    case Biweekly = 'biweekly';
    case Monthly = 'monthly';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Weekly => 'أسبوعياً',
            self::Biweekly => 'كل أسبوعين',
            self::Monthly => 'شهرياً',
            self::Custom => 'مخصص',
        };
    }

    public function discount(): float
    {
        return match ($this) {
            self::Weekly => 5.0,
            self::Biweekly => 3.0,
            self::Monthly => 2.0,
            self::Custom => 0.0,
        };
    }
}
