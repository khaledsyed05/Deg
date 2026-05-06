<?php

namespace App\Services\Team;

use App\Exceptions\Team\CannotKickCaptainException;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class KickMemberService
{
    public function kick(Team $team, User $actor, int $targetUserId): void
    {
        if ($team->captain_id === $targetUserId) {
            throw new CannotKickCaptainException;
        }

        if ($actor->id === $targetUserId) {
            throw new HttpException(422, __('team.cannot_kick_self'));
        }

        $membership = TeamMember::where('team_id', $team->id)
            ->where('user_id', $targetUserId)
            ->where('status', 'active')
            ->first();

        if (! $membership) {
            throw new HttpException(422, __('team.cannot_kick_non_member'));
        }

        DB::transaction(function () use ($membership, $team): void {
            $membership->update([
                'status' => 'removed',
                'left_at' => now(),
            ]);

            DB::table('teams')
                ->where('id', $team->id)
                ->where('total_members', '>', 0)
                ->decrement('total_members');
        });
    }
}
