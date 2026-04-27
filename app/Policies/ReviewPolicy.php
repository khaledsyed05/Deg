<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function update(User $user, Review $review): bool
    {
        return $review->canBeEditedBy($user);
    }

    public function delete(User $user, Review $review): bool
    {
        if ($review->user_id === $user->id) {
            return true;
        }

        return $user->hasRole('admin');
    }
}
