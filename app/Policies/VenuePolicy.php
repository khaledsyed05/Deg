<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Venue;

class VenuePolicy
{
    public function update(User $user, Venue $venue): bool
    {
        if ($venue->club->owner_id === $user->id) {
            return true;
        }

        return $venue->club->members()->where('users.id', $user->id)->exists();
    }

    public function delete(User $user, Venue $venue): bool
    {
        return $venue->club->owner_id === $user->id;
    }
}
