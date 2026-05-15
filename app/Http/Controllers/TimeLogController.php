<?php

namespace App\Http\Controllers;

use App\Models\TimeLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Models\PublicHoliday;
use Carbon\Carbon;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class TimeLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user        = auth()->user();
        $viewableIds = $this->_viewableUserIds($user);

        // Resolve effective user scope for both queries
        $effectiveIds = $viewableIds; // null = all
        if ($request->filled('team_id') && ($user->can('view all timesheet') || $user->can('view team timesheet'))) {
            $teamMembers  = Team::find($request->team_id)?->users()->pluck('users.id')->toArray() ?? [];
            $effectiveIds = $viewableIds !== null ? array_values(array_intersect($teamMembers, $viewableIds)) : $teamMembers;
        } elseif ($request->filled('user_id') && ($viewableIds === null || count($viewableIds) > 1)) {
            $uid          = (int) $request->user_id;
            $effectiveIds = ($viewableIds === null || in_array($uid, $viewableIds)) ? [$uid] : $viewableIds;
        }

        // ── Time logs ──
        $logsQuery = TimeLog::with(['project', 'task', 'user']);
        if ($effectiveIds !== null) $logsQuery->whereIn('user_id', $effectiveIds);
        if ($request->filled('date_from'))   $logsQuery->whereDate('date', '>=', $request->date_from);
        if ($request->filled('date_to'))     $logsQuery->whereDate('date', '<=', $request->date_to);
        if ($request->filled('project_id'))  $logsQuery->where('project_id', $request->project_id);
        if ($request->filled('task_id'))     $logsQuery->where('task_id', $request->task_id);
        if ($request->boolean('no_context')) $logsQuery->whereNull('project_id')->whereNull('task_id');

        // ── Approved OT (skip when task/no_context filter active) ──
        $otItems = collect();
        if (!$request->filled('task_id') && !$request->boolean('no_context')) {
            $otQuery = OvertimeRequest::with(['user'])->where('status', 'approved');
            if ($effectiveIds !== null) $otQuery->whereIn('user_id', $effectiveIds);
            if ($request->filled('date_from')) $otQuery->whereDate('start_at', '>=', $request->date_from);
            if ($request->filled('date_to'))   $otQuery->whereDate('start_at', '<=', $request->date_to);
            if ($request->filled('project_id')) $otQuery->where('project_id', $request->project_id);
            $otItems = $otQuery->get();
        }

        // ── Merge, sort, paginate ──
        $allItems = collect();
        foreach ($logsQuery->get() as $log) {
            $allItems->push(['_type' => 'log', '_date' => $log->date->format('Y-m-d'), '_ts' => $log->created_at?->timestamp ?? 0, '_model' => $log]);
        }
        foreach ($otItems as $ot) {
            $allItems->push(['_type' => 'ot', '_date' => Carbon::parse($ot->start_at)->format('Y-m-d'), '_ts' => $ot->created_at?->timestamp ?? 0, '_model' => $ot]);
        }
        $allItems = $allItems->sort(function ($a, $b) {
            if ($a['_date'] !== $b['_date']) return strcmp($b['_date'], $a['_date']);
            return $b['_ts'] <=> $a['_ts'];
        })->values();

        $perPage   = 20;
        $page      = max(1, (int) $request->input('page', 1));
        $logs      = new LengthAwarePaginator(
            $allItems->slice(($page - 1) * $perPage, $perPage)->values(),
            $allItems->count(),
            $perPage,
            $page,
            ['path' => $request->url()]
        );
        $logs->appends($request->except('page'));

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

        if ($request->filled('date')) {
            $jumpMonday = Carbon::parse($request->input('date'))->startOfWeek(Carbon::MONDAY);
            $thisMonday = Carbon::now()->startOfWeek(Carbon::MONDAY);
            $offset = (int) round(($jumpMonday->timestamp - $thisMonday->timestamp) / (7 * 86400));
        } else {
            $offset = (int) $request->query('offset', 0);
        }
        $groupBy = $request->query('group', 'context'); // 'context' or 'user'
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

        // Mode: individual or team
        $mode = ($request->query('mode') === 'team' && $filterTeams) ? 'team' : 'individual';

        // Resolve selected user (individual mode)
        $selectedUserId = (int) $request->query('user_id', $user->id);
        if ($viewableIds !== null && !in_array($selectedUserId, $viewableIds)) {
            $selectedUserId = $user->id;
        }

        // Resolve selected team (team mode)
        $selectedTeamId = $request->query('team_id');
        if ($mode === 'team' && !$selectedTeamId && $filterTeams) {
            $selectedTeamId = $filterTeams->first()?->id;
        }

        // Build the log query
        $logsQuery = TimeLog::whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->with(['project', 'task', 'user'])
            ->orderBy('date');

        if ($mode === 'team' && $selectedTeamId) {
            $teamMemberIds = Team::find($selectedTeamId)?->users()->pluck('users.id')->toArray() ?? [];
            if ($viewableIds !== null) {
                $teamMemberIds = array_intersect($teamMemberIds, $viewableIds);
            }
            $logsQuery->whereIn('user_id', $teamMemberIds);
        } else {
            $logsQuery->where('user_id', $selectedUserId);
        }

        $logs = $logsQuery->get();

        // Build grid rows
        $rows = [];
        if ($groupBy === 'user') {
            foreach ($logs as $log) {
                $key = 'user_' . $log->user_id;
                if (!isset($rows[$key])) {
                    $rows[$key] = [
                        'type'    => 'user',
                        'label'   => $log->user?->name ?? 'Unknown',
                        'link'    => $log->user_id ? route('users.show', $log->user_id) : null,
                        'total'   => 0,
                        'days'    => [],
                        'user_id' => $log->user_id,
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
            uasort($rows, fn($a, $b) => strcmp($a['label'], $b['label']));
        } else {
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
            uasort($rows, function ($a, $b) {
                $order = ['task' => 0, 'project' => 1, 'other' => 2];
                return ($order[$a['type']] ?? 3) <=> ($order[$b['type']] ?? 3);
            });
        }

        // Day totals
        $dayTotals = [];
        foreach ($days as $day) {
            $dk             = $day->format('Y-m-d');
            $dayTotals[$dk] = 0;
            foreach ($rows as $row) {
                $dayTotals[$dk] += $row['days'][$dk]['total'] ?? 0;
            }
        }

        $weekTotal    = $logs->sum('time_spent');
        $holidayDates = PublicHoliday::getHolidayDates($weekStart->copy(), $weekEnd->copy());

        return view('time_logs.weekly', compact(
            'days', 'rows', 'weekStart', 'weekEnd', 'offset', 'dayTotals', 'weekTotal',
            'filterUsers', 'filterTeams', 'selectedUserId', 'selectedTeamId',
            'holidayDates', 'groupBy', 'mode'
        ));
    }

    public function monthly(Request $request)
    {
        $user        = auth()->user();
        $viewableIds = $this->_viewableUserIds($user);

        // Build individual user list (null = no permission to see others)
        $filterUsers = null;
        if ($viewableIds === null) {
            $filterUsers = User::orderBy('name')->get();
        } elseif (count($viewableIds) > 1) {
            $filterUsers = User::whereIn('id', $viewableIds)->orderBy('name')->get();
        }

        // Build team list (only when multi-user view is available)
        $filterTeams = null;
        if ($filterUsers) {
            $allViewableIds = $viewableIds ?? User::pluck('id')->toArray();
            $teams = Team::whereHas('users', fn($q) => $q->whereIn('users.id', $allViewableIds))
                ->orderBy('name')->get();
            $filterTeams = $teams->isNotEmpty() ? $teams : null;
        }

        // Mode: individual or team (team only valid when teams list exists)
        $mode = ($request->query('mode') === 'team' && $filterTeams) ? 'team' : 'individual';

        // Resolve selected user (individual mode)
        $selectedUserId = (int) $request->query('user_id', $user->id);
        if ($viewableIds !== null && !in_array($selectedUserId, $viewableIds)) {
            $selectedUserId = $user->id;
        }
        $selectedUser = User::with('teams')->find($selectedUserId) ?? $user;

        // Resolve selected team (team mode)
        $selectedTeamId = $request->query('team_id');
        $selectedTeam   = null;
        if ($mode === 'team') {
            $selectedTeam = $selectedTeamId
                ? Team::with('users')->find($selectedTeamId)
                : null;
            if (!$selectedTeam && $filterTeams) {
                $selectedTeam   = $filterTeams->first()->load('users');
                $selectedTeamId = $selectedTeam?->id;
            }
        }

        // Determine target user IDs for data queries
        if ($mode === 'team' && $selectedTeam) {
            $teamUserIds   = $selectedTeam->users->pluck('id')->toArray();
            $targetUserIds = $viewableIds !== null
                ? array_intersect($teamUserIds, $viewableIds)
                : $teamUserIds;
        } else {
            $targetUserIds = [$selectedUserId];
        }

        // Month
        $monthStr   = $request->query('month', now()->format('Y-m'));
        $monthDate  = Carbon::parse($monthStr . '-01');
        $monthStart = $monthDate->copy()->startOfMonth();
        $monthEnd   = $monthDate->copy()->endOfMonth();
        $calStart   = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $calEnd     = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);
        $prevMonth  = $monthDate->copy()->subMonth()->format('Y-m');
        $nextMonth  = $monthDate->copy()->addMonth()->format('Y-m');

        // Time logs
        $timeLogs = TimeLog::whereIn('user_id', $targetUserIds)
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get();

        $logsByDay = [];
        foreach ($timeLogs as $log) {
            $dk             = $log->date->format('Y-m-d');
            $logsByDay[$dk] = ($logsByDay[$dk] ?? 0) + $log->time_spent;
        }
        $totalWork = $timeLogs->sum('time_spent');

        // OT requests
        $otRequests = OvertimeRequest::whereIn('user_id', $targetUserIds)
            ->where('status', 'approved')
            ->where('start_at', '<=', $monthEnd)
            ->where('end_at',   '>=', $monthStart)
            ->get();

        $otByDay  = [];
        $otsByDay = [];
        $totalOt  = 0;
        foreach ($otRequests as $ot) {
            $dk = Carbon::parse($ot->start_at)->toDateString();
            if ($dk >= $monthStart->toDateString() && $dk <= $monthEnd->toDateString()) {
                $otByDay[$dk]  = ($otByDay[$dk] ?? 0) + $ot->hours;
                $otsByDay[$dk][] = $ot;
                $totalOt       += $ot->hours;
            }
        }

        // Leave requests (distribute hours across covered days)
        $leaveRequests = LeaveRequest::whereIn('user_id', $targetUserIds)
            ->where('status', 'approved')
            ->where('start_at', '<=', $monthEnd)
            ->where('end_at',   '>=', $monthStart)
            ->get();

        $leaveByDay  = [];
        $leavesByDay = [];
        $totalLeave  = 0;
        foreach ($leaveRequests as $leave) {
            $lStart      = Carbon::parse($leave->start_at)->startOfDay();
            $lEnd        = Carbon::parse($leave->end_at)->startOfDay();
            $totalDays   = max(1, $lStart->diffInDays($lEnd) + 1);
            $hoursPerDay = $leave->hours / $totalDays;

            $cursor   = $lStart->copy()->max($monthStart->copy()->startOfDay());
            $clampEnd = $lEnd->copy()->min($monthEnd->copy()->startOfDay());
            while ($cursor->lte($clampEnd)) {
                $dk                = $cursor->toDateString();
                $leaveByDay[$dk]   = ($leaveByDay[$dk] ?? 0) + $hoursPerDay;
                $leavesByDay[$dk][] = ['model' => $leave, 'hours' => $hoursPerDay];
                $totalLeave       += $hoursPerDay;
                $cursor->addDay();
            }
        }

        $holidayDates = PublicHoliday::getHolidayDates($calStart->copy(), $calEnd->copy());

        return view('time_logs.monthly', compact(
            'selectedUser', 'selectedTeam', 'selectedTeamId',
            'filterUsers', 'filterTeams', 'selectedUserId', 'mode',
            'monthDate', 'monthStart', 'monthEnd', 'calStart', 'calEnd',
            'logsByDay', 'otByDay', 'leaveByDay', 'otsByDay', 'leavesByDay',
            'totalWork', 'totalOt', 'totalLeave',
            'holidayDates', 'prevMonth', 'nextMonth', 'monthStr'
        ));
    }


    /**
     * Project-based timesheet view: tasks × days and users × days grids.
     */
    public function projectView(Request $request)
    {
        $user        = auth()->user();
        $viewableIds = $this->_viewableUserIds($user);

        // Month
        $monthStr   = $request->query('month', now()->format('Y-m'));
        $monthDate  = Carbon::parse($monthStr . '-01');
        $monthStart = $monthDate->copy()->startOfMonth();
        $monthEnd   = $monthDate->copy()->endOfMonth();
        $prevMonth  = $monthDate->copy()->subMonth()->format('Y-m');
        $nextMonth  = $monthDate->copy()->addMonth()->format('Y-m');

        $days = collect();
        for ($d = $monthStart->copy(); $d->lte($monthEnd); $d->addDay()) {
            $days->push($d->copy());
        }

        $projects          = Project::orderBy('name')->get();
        $selectedProjectId = $request->query('project_id') ? (int) $request->query('project_id') : null;
        $selectedProject   = null;

        $taskRows  = [];
        $userRows  = [];
        $dayTotals = [];

        if ($selectedProjectId) {
            $selectedProject = Project::with(['users', 'teams.users', 'tasks'])->find($selectedProjectId);
        }

        if ($selectedProject) {
            // All user IDs for this project (direct + via teams), intersected with viewable scope
            $projectUserIds = $selectedProject->users->pluck('id')->toArray();
            $teamUserIds    = $selectedProject->teams->flatMap(fn($t) => $t->users->pluck('id'))->toArray();
            $allProjectUserIds = array_values(array_unique(array_merge($projectUserIds, $teamUserIds)));
            if ($viewableIds !== null) {
                $allProjectUserIds = array_values(array_intersect($allProjectUserIds, $viewableIds));
            }

            $projectUsers = User::whereIn('id', $allProjectUserIds)->orderBy('name')->get()->keyBy('id');
            $projectTasks = Task::where('project_id', $selectedProjectId)->orderBy('name')->get()->keyBy('id');

            // Time logs
            $timeLogs = TimeLog::where('project_id', $selectedProjectId)
                ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->with(['user', 'task'])
                ->get();

            // Approved OT for this project
            $otRequests = OvertimeRequest::where('project_id', $selectedProjectId)
                ->where('status', 'approved')
                ->whereDate('start_at', '>=', $monthStart->toDateString())
                ->whereDate('start_at', '<=', $monthEnd->toDateString())
                ->with('user')
                ->get();

            // ── Build Task rows ──────────────────────────────────────
            $taskRowData = [];

            foreach ($timeLogs as $log) {
                $taskKey  = $log->task_id ? 'task_' . $log->task_id : 'no_task';
                $dk       = $log->date->format('Y-m-d');
                $userObj  = $projectUsers->get($log->user_id);
                $cost     = (float) ($userObj?->hourly_rate ?? 0) * $log->time_spent;

                if (!isset($taskRowData[$taskKey])) {
                    $task = $log->task_id ? ($projectTasks->get($log->task_id) ?? $log->task) : null;
                    $taskRowData[$taskKey] = [
                        'task'           => $task,
                        'task_id'        => $log->task_id,
                        'label'          => $log->task_id
                            ? 'TK-' . $log->task_id . ($task ? ' · ' . $task->name : '')
                            : '(Không có nhiệm vụ)',
                        'days'           => [],
                        'total_hours'    => 0,
                        'total_ot'       => 0,
                        'total_cost'     => 0,
                        'total_ot_cost'  => 0,
                    ];
                }
                if (!isset($taskRowData[$taskKey]['days'][$dk])) {
                    $taskRowData[$taskKey]['days'][$dk] = ['hours' => 0, 'ot_hours' => 0, 'cost' => 0, 'ot_cost' => 0];
                }
                $taskRowData[$taskKey]['days'][$dk]['hours'] += $log->time_spent;
                $taskRowData[$taskKey]['days'][$dk]['cost']  += $cost;
                $taskRowData[$taskKey]['total_hours']        += $log->time_spent;
                $taskRowData[$taskKey]['total_cost']         += $cost;
            }

            foreach ($otRequests as $ot) {
                $taskKey    = $ot->task_id ? 'task_' . $ot->task_id : 'no_task';
                $dk         = Carbon::parse($ot->start_at)->format('Y-m-d');
                $multiplier = match ($ot->type) {
                    'OT x1.5' => 1.5, 'OT x2' => 2.0, 'OT x3' => 3.0, default => 1.0,
                };
                $userObj = $projectUsers->get($ot->user_id);
                $cost    = (float) ($userObj?->hourly_rate ?? 0) * $ot->hours * $multiplier;

                if (!isset($taskRowData[$taskKey])) {
                    $task = $ot->task_id ? $projectTasks->get($ot->task_id) : null;
                    $taskRowData[$taskKey] = [
                        'task'          => $task,
                        'task_id'       => $ot->task_id,
                        'label'         => $ot->task_id
                            ? 'TK-' . $ot->task_id . ($task ? ' · ' . $task->name : '')
                            : '(Không có nhiệm vụ)',
                        'days'          => [],
                        'total_hours'   => 0,
                        'total_ot'      => 0,
                        'total_cost'    => 0,
                        'total_ot_cost' => 0,
                    ];
                }
                if (!isset($taskRowData[$taskKey]['days'][$dk])) {
                    $taskRowData[$taskKey]['days'][$dk] = ['hours' => 0, 'ot_hours' => 0, 'cost' => 0, 'ot_cost' => 0];
                }
                $taskRowData[$taskKey]['days'][$dk]['ot_hours']  += $ot->hours;
                $taskRowData[$taskKey]['days'][$dk]['ot_cost']   += $cost;
                $taskRowData[$taskKey]['total_ot']               += $ot->hours;
                $taskRowData[$taskKey]['total_ot_cost']          += $cost;
            }
            $taskRows = $taskRowData;

            // ── Build User rows ──────────────────────────────────────
            $userRowData = [];

            foreach ($timeLogs as $log) {
                $userKey = 'user_' . $log->user_id;
                $dk      = $log->date->format('Y-m-d');
                $userObj = $projectUsers->get($log->user_id);
                $cost    = (float) ($userObj?->hourly_rate ?? 0) * $log->time_spent;

                if (!isset($userRowData[$userKey])) {
                    $userRowData[$userKey] = [
                        'user'          => $userObj ?? $log->user,
                        'user_id'       => $log->user_id,
                        'days'          => [],
                        'total_hours'   => 0,
                        'total_ot'      => 0,
                        'total_cost'    => 0,
                        'total_ot_cost' => 0,
                    ];
                }
                if (!isset($userRowData[$userKey]['days'][$dk])) {
                    $userRowData[$userKey]['days'][$dk] = ['hours' => 0, 'ot_hours' => 0, 'cost' => 0, 'ot_cost' => 0];
                }
                $userRowData[$userKey]['days'][$dk]['hours'] += $log->time_spent;
                $userRowData[$userKey]['days'][$dk]['cost']  += $cost;
                $userRowData[$userKey]['total_hours']        += $log->time_spent;
                $userRowData[$userKey]['total_cost']         += $cost;
            }

            foreach ($otRequests as $ot) {
                $userKey    = 'user_' . $ot->user_id;
                $dk         = Carbon::parse($ot->start_at)->format('Y-m-d');
                $multiplier = match ($ot->type) {
                    'OT x1.5' => 1.5, 'OT x2' => 2.0, 'OT x3' => 3.0, default => 1.0,
                };
                $userObj = $projectUsers->get($ot->user_id);
                $cost    = (float) ($userObj?->hourly_rate ?? 0) * $ot->hours * $multiplier;

                if (!isset($userRowData[$userKey])) {
                    $userRowData[$userKey] = [
                        'user'          => $userObj ?? $ot->user,
                        'user_id'       => $ot->user_id,
                        'days'          => [],
                        'total_hours'   => 0,
                        'total_ot'      => 0,
                        'total_cost'    => 0,
                        'total_ot_cost' => 0,
                    ];
                }
                if (!isset($userRowData[$userKey]['days'][$dk])) {
                    $userRowData[$userKey]['days'][$dk] = ['hours' => 0, 'ot_hours' => 0, 'cost' => 0, 'ot_cost' => 0];
                }
                $userRowData[$userKey]['days'][$dk]['ot_hours']  += $ot->hours;
                $userRowData[$userKey]['days'][$dk]['ot_cost']   += $cost;
                $userRowData[$userKey]['total_ot']               += $ot->hours;
                $userRowData[$userKey]['total_ot_cost']          += $cost;
            }

            uasort($userRowData, fn($a, $b) => strcmp($a['user']?->name ?? '', $b['user']?->name ?? ''));
            $userRows = $userRowData;

            // Day totals (from user rows to avoid double-count)
            foreach ($days as $day) {
                $dk              = $day->format('Y-m-d');
                $dayTotals[$dk]  = ['hours' => 0, 'ot_hours' => 0, 'cost' => 0, 'ot_cost' => 0];
                foreach ($userRows as $row) {
                    $dayTotals[$dk]['hours']    += $row['days'][$dk]['hours']    ?? 0;
                    $dayTotals[$dk]['ot_hours'] += $row['days'][$dk]['ot_hours'] ?? 0;
                    $dayTotals[$dk]['cost']     += $row['days'][$dk]['cost']     ?? 0;
                    $dayTotals[$dk]['ot_cost']  += $row['days'][$dk]['ot_cost']  ?? 0;
                }
            }
        }

        // Grand totals & daily stats
        $grandTotalHours   = (float) collect($dayTotals)->sum('hours');
        $grandTotalOt      = (float) collect($dayTotals)->sum('ot_hours');
        $grandTotalCost    = (float) collect($dayTotals)->sum('cost');
        $grandTotalOtCost  = (float) collect($dayTotals)->sum('ot_cost');

        $activeDayHours = array_values(array_filter(
            array_map(fn($d) => $d['hours'] + $d['ot_hours'], $dayTotals),
            fn($h) => $h > 0
        ));
        $activeDayCosts = array_values(array_filter(
            array_map(fn($d) => $d['cost'] + $d['ot_cost'], $dayTotals),
            fn($c) => $c > 0
        ));

        $maxHours    = $activeDayHours ? max($activeDayHours) : 0;
        $minHours    = $activeDayHours ? min($activeDayHours) : 0;
        $medianHours = $activeDayHours ? $this->_median($activeDayHours) : 0;
        $maxCost     = $activeDayCosts ? max($activeDayCosts)  : 0;
        $minCost     = $activeDayCosts ? min($activeDayCosts)  : 0;
        $medianCost  = $activeDayCosts ? $this->_median($activeDayCosts) : 0;

        $holidayDates  = PublicHoliday::getHolidayDates($monthStart->copy(), $monthEnd->copy());
        $canViewSalary = $user->can('view salary') || $user->can('edit all user');

        return view('time_logs.project', compact(
            'projects', 'selectedProject', 'selectedProjectId',
            'monthDate', 'monthStart', 'monthEnd', 'days',
            'prevMonth', 'nextMonth', 'monthStr',
            'taskRows', 'userRows', 'dayTotals',
            'grandTotalHours', 'grandTotalOt', 'grandTotalCost', 'grandTotalOtCost',
            'maxHours', 'minHours', 'medianHours',
            'maxCost', 'minCost', 'medianCost',
            'holidayDates', 'canViewSalary'
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
     * Compute the median of a non-empty sorted float array.
     */
    private function _median(array $arr): float
    {
        sort($arr);
        $count = count($arr);
        if ($count === 0) return 0.0;
        $mid = (int) floor($count / 2);
        return ($count % 2 === 0)
            ? ($arr[$mid - 1] + $arr[$mid]) / 2
            : (float) $arr[$mid];
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
