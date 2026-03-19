<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        if (!$user->can('module teams')) abort(403);

        // N+1 query problem
        // $team = Team::all();

        // Eager loading
        //$teams = Team::with('users')->get();

        // Eager loading with pivot tables
        $query = Team::with(['users', 'leaders'])
            ->latest();

        if ($user->can('view teams')) {
            // no filter — see all
        } elseif ($user->can('view own teams')) {
            $query->whereHas('users', fn($q) => $q->where('users.id', $user->id));
        } else {
            abort(403);
        }

        $teams = $query->paginate(10);

        return view('teams.index', compact('teams'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!auth()->user()->can('edit teams')) abort(403);

        return view('teams.form', [
            'users' => User::all(),
            'team' => null
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('edit teams')) abort(403);

        $request->validate([
            'name' => 'required|string|max:255',
            'users' => 'array',
            'leaders' => 'array',
        ]);

        $team = Team::create([
            'name' => $request->name,
        ]);

        if ($request->users) {
            $syncData = [];

            foreach ($request->users as $userId) {
                $syncData[$userId] = [
                    'is_leader' => in_array($userId, $request->leaders ?? [])
                ];
            }

            $team->users()->sync($syncData);
        }

        return redirect()->route('teams.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team)
    {
        $user = auth()->user();
        if (!$user->can('module teams')) abort(403);

        if ($user->can('view teams')) {
            // no restriction
        } elseif ($user->can('view own teams')) {
            if (!$team->users()->where('users.id', $user->id)->exists()) abort(403);
        } else {
            abort(403);
        }

        $team->load('users'); // eager load

        return view('teams.show', compact('team'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Team $team)
    {
        if (!auth()->user()->can('edit teams')) abort(403);

        $team->load('users');

        return view('teams.form', [
            'users' => User::all(),
            'team' => $team
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Team $team)
    {
        if (!auth()->user()->can('edit teams')) abort(403);

        $request->validate([
            'name' => 'required|string|max:255',
            'users' => 'array',
            'leaders' => 'array',
        ]);

        $team->update([
            'name' => $request->name,
        ]);

        $syncData = [];

        if ($request->users) {
            foreach ($request->users as $userId) {
                $syncData[$userId] = [
                    'is_leader' => in_array($userId, $request->leaders ?? [])
                ];
            }
        }

        // This will:
        // - add new users
        // - update existing ones
        // - remove unchecked users
        $team->users()->sync($syncData);

        return redirect()->route('teams.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team)
    {
        if (!auth()->user()->can('delete teams')) abort(403);

        $team->users()->detach(); // optional cleanup
        $team->delete();

        return redirect()->route('teams.index')
            ->with('success', 'Team deleted.');
    }
}
