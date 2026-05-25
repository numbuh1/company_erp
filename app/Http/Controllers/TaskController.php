<?php

namespace App\Http\Controllers;

use App\Models\OvertimeRequest;
use App\Models\PublicHoliday;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
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

        // Compute time spent per task from time_logs
        $taskIds      = $tasks->pluck('id')->toArray();
        $timeSpentMap = \App\Models\TimeLog::whereIn('task_id', $taskIds)
            ->groupBy('task_id')
            ->selectRaw('task_id, SUM(time_spent) as total')
            ->pluck('total', 'task_id')
            ->map(fn($v) => (float) $v);

        // Column preferences
        $savedCols = $user->preferences?->task_list_column_preferences;
        $colPrefs  = json_encode($savedCols ?? [
            'project' => true, 'status' => true, 'assignees' => true,
            'budget' => true, 'start_date' => true, 'due_date' => true,
        ]);

        return view('tasks.index', compact('tasks', 'users', 'projects', 'colPrefs', 'timeSpentMap'));
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
            'budget_hours'      => 'nullable|numeric|min:0',
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
    public function show(Request $request, Task $task)
    {
        $user = auth()->user();
        if (!$user->can('view all tasks') && !$this->_isAssigned($task, $user)) abort(403);

        $task->load(['project', 'assignees', 'comments.user']);

        $activities = Activity::where('subject_type', Task::class)
            ->where('subject_id', $task->id)
            ->with('causer')
            ->latest()
            ->get();

        // ── Budget / time stats ───────────────────────────────────────────────
        $taskTotalSpent = (float) TimeLog::where('task_id', $task->id)->sum('time_spent');
        $taskTotalOt    = (float) OvertimeRequest::where('task_id', $task->id)
            ->where('status', 'approved')
            ->sum('hours');
        $taskRemaining  = $task->budget_hours !== null
            ? $task->budget_hours - $taskTotalSpent - $taskTotalOt
            : null;

        // ── Timesheet tab ─────────────────────────────────────────────────────
        // Completed task → auto-range to last 30 days of recorded activity
        $isCompletedTask = in_array($task->status, ['Đã xong', 'Done']);
        $lastTaskLogDate = TimeLog::where('task_id', $task->id)->max('date');
        if (!$request->has('ts_from') && $isCompletedTask && $lastTaskLogDate) {
            $tsRangeEnd   = Carbon::parse($lastTaskLogDate);
            $tsRangeStart = $tsRangeEnd->copy()->subDays(30);
        } else {
            $tsRangeStart = Carbon::parse($request->query('ts_from', now()->startOfMonth()->toDateString()));
            $tsRangeEnd   = Carbon::parse($request->query('ts_to',   now()->endOfMonth()->toDateString()));
        }
        if ($tsRangeStart->gt($tsRangeEnd)) $tsRangeEnd = $tsRangeStart->copy()->addDays(30);
        if ($tsRangeStart->diffInDays($tsRangeEnd) > 365) $tsRangeEnd = $tsRangeStart->copy()->addDays(365);
        $tsFromStr = $tsRangeStart->toDateString();
        $tsToStr   = $tsRangeEnd->toDateString();

        $tsDays = collect();
        for ($d = $tsRangeStart->copy(); $d->lte($tsRangeEnd); $d->addDay()) {
            $tsDays->push($d->copy());
        }

        $tsTimeLogs = TimeLog::where('task_id', $task->id)
            ->whereBetween('date', [$tsFromStr, $tsToStr])
            ->with('user')
            ->get();

        $tsOtRequests = OvertimeRequest::where('task_id', $task->id)
            ->where('status', 'approved')
            ->whereDate('start_at', '>=', $tsFromStr)
            ->whereDate('start_at', '<=', $tsToStr)
            ->with('user')
            ->get();

        $tsUserIds = $tsTimeLogs->pluck('user_id')
            ->merge($tsOtRequests->pluck('user_id'))
            ->unique()->values()->toArray();
        $tsUsers = User::whereIn('id', $tsUserIds)->orderBy('name')->get()->keyBy('id');

        $tsUserRows = [];
        foreach ($tsTimeLogs as $log) {
            $key     = 'user_' . $log->user_id;
            $dk      = $log->date->format('Y-m-d');
            $userObj = $tsUsers->get($log->user_id);
            $cost    = (float) ($userObj?->hourly_rate ?? 0) * $log->time_spent;

            if (!isset($tsUserRows[$key])) {
                $tsUserRows[$key] = [
                    'user' => $userObj ?? $log->user, 'user_id' => $log->user_id,
                    'days' => [], 'total_hours' => 0, 'total_ot' => 0,
                    'total_cost' => 0, 'total_ot_cost' => 0,
                ];
            }
            if (!isset($tsUserRows[$key]['days'][$dk])) {
                $tsUserRows[$key]['days'][$dk] = ['hours' => 0, 'ot_hours' => 0, 'cost' => 0, 'ot_cost' => 0];
            }
            $tsUserRows[$key]['days'][$dk]['hours'] += $log->time_spent;
            $tsUserRows[$key]['days'][$dk]['cost']  += $cost;
            $tsUserRows[$key]['total_hours']        += $log->time_spent;
            $tsUserRows[$key]['total_cost']         += $cost;
        }

        foreach ($tsOtRequests as $ot) {
            $key        = 'user_' . $ot->user_id;
            $dk         = Carbon::parse($ot->start_at)->format('Y-m-d');
            $multiplier = match ($ot->type) {
                'OT x1.5' => 1.5, 'OT x2' => 2.0, 'OT x3' => 3.0, default => 1.0,
            };
            $userObj = $tsUsers->get($ot->user_id);
            $cost    = (float) ($userObj?->hourly_rate ?? 0) * $ot->hours * $multiplier;

            if (!isset($tsUserRows[$key])) {
                $tsUserRows[$key] = [
                    'user' => $userObj ?? $ot->user, 'user_id' => $ot->user_id,
                    'days' => [], 'total_hours' => 0, 'total_ot' => 0,
                    'total_cost' => 0, 'total_ot_cost' => 0,
                ];
            }
            if (!isset($tsUserRows[$key]['days'][$dk])) {
                $tsUserRows[$key]['days'][$dk] = ['hours' => 0, 'ot_hours' => 0, 'cost' => 0, 'ot_cost' => 0];
            }
            $tsUserRows[$key]['days'][$dk]['ot_hours']  += $ot->hours;
            $tsUserRows[$key]['days'][$dk]['ot_cost']   += $cost;
            $tsUserRows[$key]['total_ot']               += $ot->hours;
            $tsUserRows[$key]['total_ot_cost']          += $cost;
        }

        uasort($tsUserRows, fn($a, $b) => strcmp($a['user']?->name ?? '', $b['user']?->name ?? ''));

        $tsDayTotals = [];
        foreach ($tsDays as $day) {
            $dk = $day->format('Y-m-d');
            $tsDayTotals[$dk] = ['hours' => 0, 'ot_hours' => 0, 'cost' => 0, 'ot_cost' => 0];
            foreach ($tsUserRows as $row) {
                $tsDayTotals[$dk]['hours']    += $row['days'][$dk]['hours']    ?? 0;
                $tsDayTotals[$dk]['ot_hours'] += $row['days'][$dk]['ot_hours'] ?? 0;
                $tsDayTotals[$dk]['cost']     += $row['days'][$dk]['cost']     ?? 0;
                $tsDayTotals[$dk]['ot_cost']  += $row['days'][$dk]['ot_cost']  ?? 0;
            }
        }

        $tsGrandTotalHours  = (float) collect($tsDayTotals)->sum('hours');
        $tsGrandTotalOt     = (float) collect($tsDayTotals)->sum('ot_hours');
        $tsGrandTotalCost   = (float) collect($tsDayTotals)->sum('cost');
        $tsGrandTotalOtCost = (float) collect($tsDayTotals)->sum('ot_cost');

        $tsHolidayDates  = PublicHoliday::getHolidayDates($tsRangeStart->copy(), $tsRangeEnd->copy());
        $tsCanViewSalary = $user->can('view salary') || $user->can('edit all user');
        $tsInitialTab    = $request->query('tab', ($request->has('ts_from') || $request->has('ts_to')) ? 'timesheet' : 'comments');

        return view('tasks.show', compact(
            'task', 'activities',
            'taskTotalSpent', 'taskTotalOt', 'taskRemaining',
            'tsFromStr', 'tsToStr', 'tsDays',
            'tsUserRows', 'tsDayTotals',
            'tsGrandTotalHours', 'tsGrandTotalOt', 'tsGrandTotalCost', 'tsGrandTotalOtCost',
            'tsHolidayDates', 'tsCanViewSalary', 'tsInitialTab'
        ));
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
            'budget_hours'      => 'nullable|numeric|min:0',
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
