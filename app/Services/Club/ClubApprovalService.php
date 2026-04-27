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

    public function reject(Club $club, string $reason, ?int $adminId = null): Club
    {
        if ($club->status !== ClubStatus::PendingApproval) {
            throw new RuntimeException('Only clubs pending approval can be rejected.');
        }

        return $this->clubRepo->update($club, [
            'status' => ClubStatus::Rejected,
            'rejection_reason' => $reason,
            'rejected_at' => now(),
            'rejected_by' => $adminId,
        ]);
    }

    public function suspend(Club $club, string $reason, ?int $adminId = null): Club
    {
        if ($club->status !== ClubStatus::Active) {
            throw new RuntimeException('Only active clubs can be suspended.');
        }

        return $this->clubRepo->update($club, [
            'status' => ClubStatus::Suspended,
            'suspension_reason' => $reason,
            'suspended_at' => now(),
            'suspended_by' => $adminId,
        ]);
    }

    public function reactivate(Club $club): Club
    {
        if (! in_array($club->status, [ClubStatus::Inactive, ClubStatus::Suspended], true)) {
            throw new RuntimeException('Only inactive or suspended clubs can be reactivated.');
        }

        return $this->clubRepo->update($club, [
            'status' => ClubStatus::Active,
            'suspension_reason' => null,
            'unsuspended_at' => now(),
        ]);
    }
}
