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

        if ($user->can($all_permission)) {
            return true;
        }

        if (!$user->can($team_permission)) {
            return abort(403);
        }

        $requester = $request->user;

        if (!Helper::checkLeadOfTeamMate($requester)) {
            return abort(403);
        }

        // When the requester is also a team leader, only their supervisor
        // or another leader of the same team may approve.
        $requesterIsLeader = $requester->teams()->wherePivot('is_leader', true)->exists();

        if ($requesterIsLeader) {
            $isSupervisor = $requester->supervisors()->where('users.id', $user->id)->exists();
            if ($isSupervisor) {
                return true;
            }

            // Check if approver is also a leader in a shared team
            $requesterTeamIds = $requester->teams()->pluck('teams.id');
            $approverLeadsSharedTeam = $user->teams()
                ->wherePivot('is_leader', true)
                ->whereIn('teams.id', $requesterTeamIds)
                ->exists();

            if (!$approverLeadsSharedTeam) {
                return abort(403);
            }
        }

        return true;
    }
}
