<?php

namespace App\Services\Team;

use App\Exceptions\Team\InviteExpiredException;
use App\Exceptions\Team\InviteUsedUpException;
use App\Exceptions\Team\TeamFullException;
use App\Models\TeamInvite;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UseInviteService
{
    /**
     * @return array{team_id: int, already_member: bool}
     */
    public function use(TeamInvite $invite, User $user): array
    {
        if ($invite->isExpired()) {
            throw new InviteExpiredException;
        }

        if ($invite->isUsedUp()) {
            throw new InviteUsedUpException;
        }

        $team = $invite->team;

        if ($team === null) {
            throw new InviteExpiredException(__('team.invite_not_found'));
        }

        if ($team->hasMember($user->id)) {
            return ['team_id' => $team->id, 'already_member' => true];
        }

        if ($team->isFull()) {
            throw new TeamFullException;
        }

        DB::transaction(function () use ($team, $invite, $user): void {
            $existing = TeamMember::where('team_id', $team->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existing) {
                $existing->update([
                    'status' => 'active',
                    'role' => 'member',
                    'joined_at' => now(),
                    'left_at' => null,
                ]);
            } else {
                TeamMember::create([
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                    'role' => 'member',
                    'status' => 'active',
                    'joined_at' => now(),
                    'invited_by' => $invite->created_by,
                ]);
            }

            $team->increment('total_members');
            $invite->increment('uses_count');
        });

        return ['team_id' => $team->id, 'already_member' => false];
    }
}
