<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(?User $user, Team $team): bool
    {
        if ($team->is_public) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        return $team->hasMember($user->id);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['player', 'admin']);
    }

    public function update(User $user, Team $team): bool
    {
        return $user->hasRole('admin') || $team->captain_id === $user->id;
    }

    public function delete(User $user, Team $team): bool
    {
        return $user->hasRole('admin') || $team->captain_id === $user->id;
    }

    public function kick(User $user, Team $team): bool
    {
        return $user->hasRole('admin') || $team->captain_id === $user->id;
    }

    /**
     * Captain-only by design — even admins cannot forcibly transfer
     * captaincy. Document this in the discovery doc and CHANGELOG.
     */
    public function transferCaptain(User $user, Team $team): bool
    {
        return $team->captain_id === $user->id;
    }

    public function invite(User $user, Team $team): bool
    {
        return $user->hasRole('admin') || $team->captain_id === $user->id;
    }
}
