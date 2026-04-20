<?php

namespace App\Services\Club;

use App\Enums\ClubStatus;
use App\Models\Club;
use App\Repositories\Contracts\ClubRepositoryInterface;
use RuntimeException;

class ClubApprovalService
{
    public function __construct(
        private ClubRepositoryInterface $clubRepo,
    ) {}

    public function approve(Club $club, int $adminId): Club
    {
        if ($club->status !== ClubStatus::PendingApproval) {
            throw new RuntimeException('Only clubs pending approval can be approved.');
        }

        return $this->clubRepo->approve($club, $adminId);
    }

    public function reject(Club $club, string $reason): Club
    {
        if ($club->status !== ClubStatus::PendingApproval) {
            throw new RuntimeException('Only clubs pending approval can be rejected.');
        }

        return $this->clubRepo->reject($club, $reason);
    }

    public function suspend(Club $club): Club
    {
        return $this->clubRepo->update($club, ['status' => ClubStatus::Suspended]);
    }

    public function reactivate(Club $club): Club
    {
        if (! in_array($club->status, [ClubStatus::Inactive, ClubStatus::Suspended])) {
            throw new RuntimeException('Only inactive or suspended clubs can be reactivated.');
        }

        return $this->clubRepo->update($club, ['status' => ClubStatus::Active]);
    }
}
