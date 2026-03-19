<?php

namespace App\Helper;

use App\Models\User;
use App\Models\Team;

class Helper
{
	public static function checkLeadOfTeamMate($user) {
		$current_user = auth()->user();

		// Must be a leader of a team that the requester belongs to
	    $leaderTeamIds = $current_user->teams()->wherePivot('is_leader', true)->pluck('teams.id');

	    $requesterInTeam = $user
	        ->teams()
	        ->whereIn('teams.id', $leaderTeamIds)
	        ->exists();

	    if (!$requesterInTeam) {
	        return false;
	    }

	    return true;
	}

	public static function authorizeRequest(string $all_permission, string $team_permission, $request) {
        $user = auth()->user();

        if (!$user->can($all_permission)) {
            if(!$user->can($team_permission)) {
                return abort(403);
            }

            $check_leader = Helper::checkLeadOfTeamMate($request->user);
            if(!$check_leader) {
                return abort(403);
            }
        }

        return true;
    }
}
