<?php

namespace App\Policies;

use App\Models\Settlement;
use App\Models\User;

class SettlementPolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Settlement $settlement): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Settlement $settlement): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        // Club owner can view their own settlements
        return $settlement->club->owner_id === $user->id;
    }
}
