<?php

namespace App\Services\Team;

use App\Exceptions\Team\CannotTransferToNonMemberException;
use App\Models\AuditLog;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TransferCaptainService
{
    public function transfer(Team $team, User $currentCaptain, int $newCaptainUserId): Team
    {
        if ($team->captain_id === $newCaptainUserId) {
            // Idempotent — already the captain. No-op, no error.
            return $team->fresh();
        }

        $newCaptainMembership = TeamMember::where('team_id', $team->id)
            ->where('user_id', $newCaptainUserId)
            ->where('status', 'active')
            ->first();

        if (! $newCaptainMembership) {
            throw new CannotTransferToNonMemberException;
        }

        $oldCaptainId = $team->captain_id;

        $updated = DB::transaction(function () use ($team, $currentCaptain, $newCaptainUserId, $newCaptainMembership): Team {
            $team->update(['captain_id' => $newCaptainUserId]);

            $newCaptainMembership->update(['role' => 'captain']);

            TeamMember::where('team_id', $team->id)
                ->where('user_id', $currentCaptain->id)
                ->update(['role' => 'member']);

            return $team->fresh();
        });

        AuditLog::record(
            action: 'team.transfer_captain',
            userId: $currentCaptain->id,
            subject: $updated,
            changes: [
                'old_captain_id' => $oldCaptainId,
                'new_captain_id' => $newCaptainUserId,
            ],
            ip: request()?->ip(),
        );

        return $updated;
    }
}
