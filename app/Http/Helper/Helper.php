<?php

namespace App\Helper;

use App\Models\User;
use App\Models\Team;

class Helper
{
	public static function checkLeadOfTeamMate($user) {
		$current_user = auth()->user;

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
}
