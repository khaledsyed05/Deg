<?php

namespace App\Services\Team;

use App\Models\Team;
use Illuminate\Support\Facades\DB;

class DeleteTeamService
{
    public function delete(Team $team): void
    {
        DB::transaction(function () use ($team): void {
            // team_members and team_invites cascade via FK; nothing else
            // currently references teams with non-cascade rules. The
            // discovery doc records the hard-delete decision.
            $team->delete();
        });
    }
}
