<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VenueWaitlist;

class VenueWaitlistPolicy
{
    public function delete(User $user, VenueWaitlist $waitlist): bool
    {
        return $waitlist->user_id === $user->id;
    }
}
