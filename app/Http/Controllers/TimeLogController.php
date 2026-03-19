<?php

namespace App\Http\Controllers;

use App\Models\TimeLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TimeLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user        = auth()->user();
        $viewableIds = $this->_viewableUserIds($user);

        $query = TimeLog::with(['project', 'task', 'user']);

        if ($viewableIds !== null) {
            $query->whereIn('user_id', $viewableIds);
        }

        // Team filter (for those who can see team/all)
        if ($request->filled('team_id') && ($user->can('view all timesheet') || $user->can('view team timesheet'))) {
            $teamMemberIds = Team::find($request->team_id)?->users()->pluck('users.id') ?? collect();
            $query->whereIn('user_id', $teamMemberIds);
        } elseif ($request->filled('user_id') && ($viewableIds === null || count($viewableIds) > 1)) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date'))        $query->whereDate('date', $request->date);
        if ($request->filled('project_id'))  $query->where('project_id', $request->project_id);
        if ($request->filled('task_id'))     $query->where('task_id', $request->task_id);
        if ($request->boolean('no_context')) $query->whereNull('project_id')->whereNull('task_id');

        $logs     = $query->orderByDesc('date')->orderByDesc('created_at')->paginate(20)->withQueryString();
        $projects = Project::orderBy('name')->get();
        $tasks    = Task::orderBy('name')->get();

        // Build user/team lists for filter dropdowns
        $users = null;
        $teams = null;
        if ($viewableIds === null) {
            $users = User::orderBy('name')->get();
            $teams = Team::orderBy('name')->get();
        } elseif (count($viewableIds) > 1) {
            $users = User::whereIn('id', $viewableIds)->orderBy('name')->get();
            if ($user->can('view team timesheet')) {
                $teams = $user->teams()->get();
            }
        }

        return view('time_logs.index', compact('logs', 'projects', 'tasks', 'users', 'teams'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user();
        if (!$user->can('edit timesheet') && !$user->can('edit team timesheet') && !$user->can('edit own timesheet')) {
            abort(403);
        }

        $taskId    = request('task_id');
        $projectId = request('project_id');
        $task      = $taskId ? Task::find($taskId) : null;
        $projectId = $projectId ?? $task?->project_id;

        return view('time_logs.form', [
            'projects'           => Project::orderBy('name')->get(),
            'tasks'              => Task::with('project')->orderBy('name')->get(),
            'default_project_id' => $projectId,
            'default_task_id'    => $taskId,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user->can('edit timesheet') && !$user->can('edit team timesheet') && !$user->can('edit own timesheet')) {
            abort(403);
        }

        $data = $request->validate([
            'project_id'  => 'nullable|integer|exists:projects,id',
            'task_id'     => 'nullable|integer|exists:tasks,id',
            'description' => 'nullable|string',
            'date'        => 'required|date',
            'time_spent'  => 'required|numeric|min:0.25|max:24',
        ]);

        $data['user_id'] = auth()->id();
        TimeLog::create($data);

        return redirect()->route('time-logs.index')->with('success', 'Time logged successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(TimeLog $timeLog)
    {
        $user        = auth()->user();
        $viewableIds = $this->_viewableUserIds($user);
        if ($viewableIds !== null && !in_array($timeLog->user_id, $viewableIds)) abort(403);

        $timeLog->load(['project', 'task', 'user']);
        $canEdit = $this->_canEditLog($user, $timeLog);

        return view('time_logs.show', compact('timeLog', 'canEdit'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TimeLog $timeLog)
    {
        $user = auth()->user();
        if (!$this->_canEditLog($user, $timeLog)) abort(403);

        return view('time_logs.form', [
            'timeLog'            => $timeLog,
            'projects'           => Project::orderBy('name')->get(),
            'tasks'              => Task::with('project')->orderBy('name')->get(),
            'default_project_id' => $timeLog->project_id,
            'default_task_id'    => $timeLog->task_id,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TimeLog $timeLog)
    {
        $user = auth()->user();
        if (!$this->_canEditLog($user, $timeLog)) abort(403);

        $data = $request->validate([
            'project_id'  => 'nullable|integer|exists:projects,id',
            'task_id'     => 'nullable|integer|exists:tasks,id',
            'description' => 'nullable|string',
            'date'        => 'required|date',
            'time_spent'  => 'required|numeric|min:0.25|max:24',
        ]);

        $timeLog->update($data);
        return redirect()->route('time-logs.show', $timeLog)->with('success', 'Time log updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TimeLog $timeLog)
    {
        $user = auth()->user();
        if (!$this->_canEditLog($user, $timeLog)) abort(403);
        $timeLog->delete();
        return redirect()->route('time-logs.index')->with('success', 'Time log deleted.');
    }

    /**
     * Show Weekly View
     */
    public function weekly(Request $request)
    {
        $user        = auth()->user();
        $viewableIds = $this->_viewableUserIds($user);

        $offset    = (int) $request->query('offset', 0);
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->addWeeks($offset);
        $weekEnd   = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $days = collect();
        for ($d = $weekStart->copy(); $d->lte($weekEnd); $d->addDay()) {
            $days->push($d->copy());
        }

        // Build user/team filter lists for the dropdowns
        $filterUsers = null;
        $filterTeams = null;
        if ($viewableIds === null) {
            $filterUsers = User::orderBy('name')->get();
            $filterTeams = Team::orderBy('name')->get();
        } elseif (count($viewableIds) > 1) {
            $filterUsers = User::whereIn('id', $viewableIds)->orderBy('name')->get();
            if ($user->can('view team timesheet')) {
                $filterTeams = $user->teams()->get();
            }
        }

        // Determine active filter values
        $selectedTeamId = $request->query('team_id');
        $selectedUserId = $request->query('user_id', $user->id);

        // Build the log query
        $logsQuery = TimeLog::whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->with(['project', 'task'])
            ->orderBy('date');

        if ($selectedTeamId && ($user->can('view all timesheet') || $user->can('view team timesheet'))) {
            // Team mode: combine all members' logs
            $teamMemberIds = Team::find($selectedTeamId)?->users()->pluck('users.id') ?? collect();
            $logsQuery->whereIn('user_id', $teamMemberIds);
            $selectedUserId = null;
        } else {
            // User mode: clamp to allowed IDs if restricted
            if ($viewableIds !== null) {
                $selectedUserId = in_array((int) $selectedUserId, $viewableIds)
                    ? (int) $selectedUserId
                    : $user->id;
            }
            $logsQuery->where('user_id', $selectedUserId);
        }

        $logs = $logsQuery->get();

        // Build grid rows
        $rows = [];
        foreach ($logs as $log) {
            if ($log->task_id) {
                $key   = 'task_' . $log->task_id;
                $label = 'TK-' . $log->task_id . ($log->task ? ' · ' . $log->task->name : ' (deleted)');
                $link  = $log->task ? route('tasks.show', $log->task_id) : null;
                $type  = 'task';
            } elseif ($log->project_id) {
                $key   = 'project_' . $log->project_id;
                $label = 'PJ-' . $log->project_id . ($log->project ? ' · ' . $log->project->name : ' (deleted)');
                $link  = $log->project ? route('projects.show', $log->project_id) : null;
                $type  = 'project';
            } else {
                $key   = 'other';
                $label = 'Other';
                $link  = null;
                $type  = 'other';
            }

            if (!isset($rows[$key])) {
                $rows[$key] = [
                    'type'       => $type,
                    'label'      => $label,
                    'link'       => $link,
                    'total'      => 0,
                    'days'       => [],
                    'project_id' => $log->project_id,
                    'task_id'    => $log->task_id,
                ];
            }

            $dayKey = $log->date->format('Y-m-d');
            if (!isset($rows[$key]['days'][$dayKey])) {
                $rows[$key]['days'][$dayKey] = ['total' => 0, 'logs' => [], 'descriptions' => []];
            }
            $rows[$key]['days'][$dayKey]['total']         += $log->time_spent;
            $rows[$key]['days'][$dayKey]['logs'][]         = $log;
            $rows[$key]['days'][$dayKey]['descriptions'][] = $log->description ?? '';
            $rows[$key]['total']                          += $log->time_spent;
        }

        // Sort: tasks first, then projects, then other
        uasort($rows, function ($a, $b) {
            $order = ['task' => 0, 'project' => 1, 'other' => 2];
            return ($order[$a['type']] ?? 3) <=> ($order[$b['type']] ?? 3);
        });

        // Day totals
        $dayTotals = [];
        foreach ($days as $day) {
            $dk             = $day->format('Y-m-d');
            $dayTotals[$dk] = 0;
            foreach ($rows as $row) {
                $dayTotals[$dk] += $row['days'][$dk]['total'] ?? 0;
            }
        }

        $weekTotal = $logs->sum('time_spent');

        return view('time_logs.weekly', compact(
            'days', 'rows', 'weekStart', 'weekEnd', 'offset', 'dayTotals', 'weekTotal',
            'filterUsers', 'filterTeams', 'selectedUserId', 'selectedTeamId'
        ));
    }

    /**
     * Determine which user IDs the current user may view.
     * Returns null if unrestricted (all users).
     */
    private function _viewableUserIds($user): ?array
    {
        if ($user->can('view all timesheet')) {
            return null;
        }
        if ($user->can('view team timesheet')) {
            $teamUserIds = $user->teamMembers()->pluck('id')->toArray();
            return array_unique(array_merge([$user->id], $teamUserIds));
        }
        if ($user->can('view own timesheet')) {
            return [$user->id];
        }
        abort(403);
    }

    /**
     * Check if the current user can edit/delete a specific time log.
     */
    private function _canEditLog($user, TimeLog $timeLog): bool
    {
        if ($user->can('edit timesheet')) return true;
        if ($user->can('edit team timesheet')) {
            $teamUserIds = $user->teamMembers()->pluck('id')->toArray();
            return in_array($timeLog->user_id, array_merge([$user->id], $teamUserIds));
        }
        if ($user->can('edit own timesheet')) {
            return $timeLog->user_id === $user->id;
        }
        return false;
    }
}
