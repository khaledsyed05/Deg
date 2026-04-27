<?php

namespace App\Enums;

enum TeamType: string
{
    case Casual = 'casual';
    case Regular = 'regular';
    case Competitive = 'competitive';

    public function label(): string
    {
        return match ($this) {
            self::Casual => 'فريق عادي',
            self::Regular => 'فريق منتظم',
            self::Competitive => 'فريق تنافسي',
        };
    }
}
