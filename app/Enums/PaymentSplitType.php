<?php

namespace App\Enums;

enum PaymentSplitType: string
{
    case Full = 'full';
    case Equal = 'equal';
    case Custom = 'custom';
    case Individual = 'individual';

    public function label(): string
    {
        return match ($this) {
            self::Full => 'دفع كامل من الكابتن',
            self::Equal => 'تقسيم متساوي',
            self::Custom => 'تقسيم مخصص',
            self::Individual => 'دفع فردي',
        };
    }
}
