<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnnouncementController extends Controller
{
    /**
     * Scope query to announcements visible to the current user.
     */
    private function _visibilityScope($query, $user): void
    {
        // Admins and editors see everything
        if ($user->can('edit announcements') || $user->can('edit all user') || $user->can('view all user')) {
            return;
        }

        // Others see company-wide (no teams) OR announcements for their teams
        $userTeamIds = $user->teams()->pluck('teams.id');
        $query->where(function ($q) use ($userTeamIds) {
            $q->whereDoesntHave('teams')
              ->orWhereHas('teams', fn($tq) => $tq->whereIn('teams.id', $userTeamIds));
        });
    }

    public function index()
    {
        $user  = auth()->user();
        $query = Announcement::with(['author', 'teams'])->latest();
        $this->_visibilityScope($query, $user);

        $announcements = $query->paginate(15);
        return view('announcements.index', compact('announcements'));
    }

    public function create()
    {
        if (!auth()->user()->can('edit announcements')) abort(403);
        $teams = Team::orderBy('name')->get();
        return view('announcements.form', compact('teams'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->can('edit announcements')) abort(403);

        $data = $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $data['user_id'] = auth()->id();
        $announcement = Announcement::create($data);

        // Sync teams — empty means company-wide
        if ($request->boolean('all_company') || empty($request->teams)) {
            $announcement->teams()->detach();
        } else {
            $announcement->teams()->sync($request->teams);
        }

        return redirect()->route('announcements.show', $announcement)->with('success', 'Announcement published.');
    }

    public function show(Announcement $announcement)
    {
        $user = auth()->user();
        $announcement->load('teams');

        // Enforce visibility
        if (!$user->can('edit announcements') && !$user->can('edit all user') && !$user->can('view all user')) {
            if ($announcement->teams->isNotEmpty()) {
                $userTeamIds = $user->teams()->pluck('teams.id');
                if ($announcement->teams->pluck('id')->intersect($userTeamIds)->isEmpty()) {
                    abort(403);
                }
            }
        }

        return view('announcements.show', compact('announcement'));
    }

    public function edit(Announcement $announcement)
    {
        if (!auth()->user()->can('edit announcements')) abort(403);
        $announcement->load('teams');
        $teams = Team::orderBy('name')->get();
        return view('announcements.form', compact('announcement', 'teams'));
    }

    public function update(Request $request, Announcement $announcement)
    {
        if (!auth()->user()->can('edit announcements')) abort(403);

        $data = $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $announcement->update($data);

        // Sync teams
        if ($request->boolean('all_company') || empty($request->teams)) {
            $announcement->teams()->detach();
        } else {
            $announcement->teams()->sync($request->teams);
        }

        return redirect()->route('announcements.show', $announcement)->with('success', 'Announcement updated.');
    }

    public function destroy(Announcement $announcement)
    {
        if (!auth()->user()->can('delete announcements')) abort(403);
        $announcement->delete();
        return redirect()->route('announcements.index')->with('success', 'Announcement deleted.');
    }

    public function uploadImage(Request $request)
    {
        $request->validate(['image' => 'required|image|max:5120']);
        $path = $request->file('image')->store('announcement_images', 'public');
        return response()->json(['url' => Storage::disk('public')->url($path)]);
    }
}
