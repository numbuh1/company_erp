<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Team;
use App\Models\User;

class TeamUserController extends Controller
{
    public function store(Request $request, Team $team)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'is_leader' => 'required|boolean',
        ]);

        $team->users()->syncWithoutDetaching([
            $request->user_id => ['is_leader' => $request->is_leader]
        ]);

        return back();
    }

    public function destroy(Team $team, User $user)
    {
        $team->users()->detach($user->id);

        return back();
    }
}