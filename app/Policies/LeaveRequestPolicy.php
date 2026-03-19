<?php

namespace App\Policies;

use App\Models\User;

class LeaveRequestPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function approve(User $user, LeaveRequest $leave)
    {
        if ($user->can('approve a;; leaves')) {
            return $user->pluck('id')
                ->contains($leave->user_id);
        } else if ($user->can('approve team leaves')) {
            return $user->teamMembers()
                ->pluck('id')
                ->contains($leave->user_id);
        }

        return false;        
    }
}
