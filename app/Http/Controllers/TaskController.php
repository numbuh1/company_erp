<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = Task::with(['project', 'assignees']);
        $this->_scopeQuery($query, $user);

        // Search by name or TK-id
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
                $numId = (int) preg_replace('/[^0-9]/', '', $search);
                if ($numId > 0) {
                    $q->orWhere('id', $numId);
                }
            });
        }

        // Filter by project
        if ($projectId = $request->input('project_id')) {
            $query->where('project_id', (int) $projectId);
        }

        // Filter by assignee
        if ($assigneeId = $request->input('assignee_id')) {
            $query->whereHas('assignees', fn ($q) => $q->where('users.id', (int) $assigneeId));
        }

        // Sort
        match ($request->input('sort', 'latest')) {
            'id_asc'  => $query->orderBy('id', 'asc'),
            'id_desc' => $query->orderBy('id', 'desc'),
            'due_asc' => $query->orderBy('expected_end_date', 'asc'),
            'due_desc'=> $query->orderBy('expected_end_date', 'desc'),
            default   => $query->latest(),
        };

        $tasks = $query->paginate(20)->withQueryString();
        $users = User::orderBy('name')->get();

        // Projects for the filter dropdown — scoped by permission
        if ($user->can('view all projects')) {
            $projects = Project::orderBy('name')->get();
        } elseif ($user->can('view assigned projects')) {
            $teamIds  = $user->teams()->pluck('teams.id');
            $projects = Project::where(function ($q) use ($user, $teamIds) {
                $q->whereHas('users', fn ($q) => $q->where('users.id', $user->id))
                  ->orWhereHas('teams', fn ($q) => $q->whereIn('teams.id', $teamIds));
            })->orderBy('name')->get();
        } else {
            $projects = collect();
        }

        return view('tasks.index', compact('tasks', 'users', 'projects'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!auth()->user()->can('edit tasks')) abort(403);
        $projects = Project::with(['users', 'teams.users'])->orderBy('name')->get();
        $projectMembers = $projects->mapWithKeys(fn($p) => [
            $p->id => $p->users->pluck('id')
                ->merge($p->teams->flatMap->users->pluck('id'))
                ->unique()->values()->toArray()
        ]);
        return view('tasks.form', [
            'projects'           => $projects,
            'users'              => User::orderBy('name')->get(),
            'projectMembers'     => $projectMembers,
            'default_project_id' => request('project_id'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('edit tasks')) abort(403);

        $data = $request->validate([
            'project_id'        => 'nullable|integer|exists:projects,id',
            'name'              => 'required|string|max:255',
            'description'       => 'nullable|string',
            'progress'          => 'nullable|integer|min:0|max:100',
            'start_date'        => 'nullable|date',
            'expected_end_date' => 'nullable|date',
            'actual_end_date'   => 'nullable|date',
            'assignees'         => 'nullable|array',
            'status'            => 'nullable|string',
        ]);

        $task = Task::create($data);
        $task->assignees()->sync($request->assignees ?? []);

        return redirect()->route('tasks.show', $task)->with('success', 'Task created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task)
    {
        $user = auth()->user();
        if (!$user->can('view all tasks') && !$this->_isAssigned($task, $user)) abort(403);

        $task->load(['project', 'assignees', 'comments.user']);

        $activities = Activity::where('subject_type', Task::class)
            ->where('subject_id', $task->id)
            ->with('causer')
            ->latest()
            ->get();

        return view('tasks.show', compact('task', 'activities'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Task $task)
    {
        $user = auth()->user();
        if (!$user->can('edit tasks')) {
            if (!$user->can('edit assigned tasks') || !$this->_isAssigned($task, $user)) abort(403);
        }

        $projects = Project::with(['users', 'teams.users'])->orderBy('name')->get();
        $projectMembers = $projects->mapWithKeys(fn($p) => [
            $p->id => $p->users->pluck('id')
                ->merge($p->teams->flatMap->users->pluck('id'))
                ->unique()->values()->toArray()
        ]);
        $task->load('assignees');
        return view('tasks.form', [
            'task'           => $task,
            'projects'       => $projects,
            'users'          => User::orderBy('name')->get(),
            'projectMembers' => $projectMembers,
        ]);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task)
    {
        $user = auth()->user();
        if (!$user->can('edit tasks')) {
            if (!$user->can('edit assigned tasks') || !$this->_isAssigned($task, $user)) abort(403);
        }

        $data = $request->validate([
            'project_id'        => 'nullable|integer|exists:projects,id',
            'name'              => 'required|string|max:255',
            'description'       => 'nullable|string',
            'progress'          => 'nullable|integer|min:0|max:100',
            'start_date'        => 'nullable|date',
            'expected_end_date' => 'nullable|date',
            'actual_end_date'   => 'nullable|date',
            'assignees'         => 'nullable|array',
            'status'            => 'nullable|string',
        ]);

        $task->update($data);
        $task->assignees()->sync($request->assignees ?? []);

        return redirect()->route('tasks.show', $task)->with('success', 'Task updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        if (!auth()->user()->can('delete tasks')) abort(403);
        $task->delete();
        return redirect()->route('tasks.index')->with('success', 'Task deleted.');
    }

    /**
     * Search task
     */
    public function search(Request $request)
    {
        $user      = auth()->user();
        $q         = $request->get('q', '');
        $projectId = $request->get('project_id');
        $query     = Task::query();
        $this->_scopeQuery($query, $user);

        if ($projectId) $query->where('project_id', $projectId);
        if ($q)         $query->where('name', 'like', "%{$q}%");

        $tasks = $query
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'project_id']);

        return response()->json($tasks->map(fn($t) => [
            'id'   => $t->id,
            'text' => 'TK-' . $t->id . ' ' . $t->name,
        ]));
    }

    /*
     * Check if current user is assigned to the Project
     */
    private function _isAssigned(Task $task, $user): bool
    {
        return $task->assignees()->where('users.id', $user->id)->exists();
    }

    /*
     * Check list of projects to show based on user's permission
     */
    private function _scopeQuery($query, $user): void
    {
        if ($user->can('view all tasks')) {
            return;
        }

        if ($user->can('view assigned tasks')) {
            $query->whereHas('assignees', fn($q) => $q->where('users.id', $user->id));
            return;
        }

        abort(403);
    }
}
