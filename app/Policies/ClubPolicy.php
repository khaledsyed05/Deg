<?php

namespace App\Policies;

use App\Models\Club;
use App\Models\User;

class ClubPolicy
{
    public function approve(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function reject(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Club $club): bool
    {
        if ($club->owner_id === $user->id) {
            return true;
        }

        return $club->members()->where('users.id', $user->id)->exists();
    }

    public function manageVenues(User $user, Club $club): bool
    {
        if ($club->owner_id === $user->id) {
            return true;
        }

        return $club->members()->where('users.id', $user->id)->exists();
    }

    public function manageStaff(User $user, Club $club): bool
    {
        return $club->owner_id === $user->id;
    }
}
