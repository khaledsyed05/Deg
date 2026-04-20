<?php

namespace App\Services\Club;

use App\Models\Club;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class ClubStaffService
{
    public function __construct(
        private UserRepositoryInterface $userRepo,
    ) {}

    public function attach(Club $club, int $userId): void
    {
        $user = $this->userRepo->findOrFail($userId);

        if ($club->managers()->where('users.id', $user->id)->exists()) {
            throw new RuntimeException('User is already a staff member of this club.');
        }

        $club->managers()->attach($user->id);
    }

    public function detach(Club $club, int $userId): void
    {
        $club->managers()->detach($userId);
    }

    public function list(Club $club): Collection
    {
        return $this->userRepo->findClubStaff($club->id);
    }
}
