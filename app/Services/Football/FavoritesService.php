<?php

namespace App\Services\Football;

use App\Exceptions\Football\LeagueAlreadyFollowedException;
use App\Exceptions\Football\TeamAlreadyFavoritedException;
use App\Models\Football\League;
use App\Models\Football\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FavoritesService
{
    public function addFavoriteTeam(
        User $user,
        int $teamId,
        bool $notifyMatches = true,
        bool $notifyGoals = false,
        bool $notifyResults = true,
    ): Team {
        $team = Team::findOrFail($teamId);

        if ($user->favoriteTeams()->where('team_id', $teamId)->exists()) {
            throw new TeamAlreadyFavoritedException('Team is already in favorites');
        }

        $maxOrder = (int) $user->favoriteTeams()->max('user_favorite_teams.display_order');

        $user->favoriteTeams()->attach($teamId, [
            'display_order' => $maxOrder + 1,
            'notify_matches' => $notifyMatches,
            'notify_goals' => $notifyGoals,
            'notify_results' => $notifyResults,
        ]);

        return $team;
    }

    public function removeFavoriteTeam(User $user, int $teamId): void
    {
        $user->favoriteTeams()->detach($teamId);
    }

    public function reorderFavoriteTeams(User $user, array $orderedTeamIds): void
    {
        DB::transaction(function () use ($user, $orderedTeamIds) {
            foreach ($orderedTeamIds as $index => $teamId) {
                $user->favoriteTeams()->updateExistingPivot($teamId, [
                    'display_order' => $index + 1,
                ]);
            }
        });
    }

    public function followLeague(User $user, string $leagueCode, bool $notifyMatches = true): League
    {
        $league = League::where('code', $leagueCode)->firstOrFail();

        if ($user->followedLeagues()->where('league_id', $league->id)->exists()) {
            throw new LeagueAlreadyFollowedException('League is already followed');
        }

        $maxOrder = (int) $user->followedLeagues()->max('user_followed_leagues.display_order');

        $user->followedLeagues()->attach($league->id, [
            'display_order' => $maxOrder + 1,
            'notify_matches' => $notifyMatches,
        ]);

        return $league;
    }

    public function unfollowLeague(User $user, int $leagueId): void
    {
        $user->followedLeagues()->detach($leagueId);
    }

    public function reorderFollowedLeagues(User $user, array $orderedLeagueIds): void
    {
        DB::transaction(function () use ($user, $orderedLeagueIds) {
            foreach ($orderedLeagueIds as $index => $leagueId) {
                $user->followedLeagues()->updateExistingPivot($leagueId, [
                    'display_order' => $index + 1,
                ]);
            }
        });
    }
}
