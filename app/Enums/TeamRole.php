<?php

namespace App\Enums;

enum TeamRole: string
{
    case Captain = 'captain';
    case Admin = 'admin';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Captain => 'كابتن',
            self::Admin => 'مدير',
            self::Member => 'عضو',
        };
    }

    /**
     * @return array<int, string>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Captain => [
                'invite_members',
                'remove_members',
                'create_bookings',
                'update_team',
                'delete_team',
                'transfer_captain',
            ],
            self::Admin => [
                'invite_members',
                'create_bookings',
            ],
            self::Member => [],
        };
    }
}
