<?php

namespace App\Services\Team;

use App\Exceptions\Team\TooManyInvitesException;
use App\Models\Team;
use App\Models\TeamInvite;
use App\Models\User;
use Illuminate\Support\Str;

class GenerateInviteService
{
    private const MAX_ACTIVE_INVITES_PER_TEAM = 5;

    public function generate(Team $team, User $actor, ?\DateTimeInterface $expiresAt = null, ?int $maxUses = null): TeamInvite
    {
        $activeCount = $team->invites()
            ->where(function ($q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->where(function ($q): void {
                $q->whereNull('max_uses')->orWhereColumn('uses_count', '<', 'max_uses');
            })
            ->count();

        if ($activeCount >= self::MAX_ACTIVE_INVITES_PER_TEAM) {
            throw new TooManyInvitesException;
        }

        do {
            $code = Str::upper(Str::random(8));
        } while (TeamInvite::where('code', $code)->exists());

        return TeamInvite::create([
            'team_id' => $team->id,
            'code' => $code,
            'created_by' => $actor->id,
            'expires_at' => $expiresAt,
            'max_uses' => $maxUses,
            'uses_count' => 0,
        ]);
    }
}
