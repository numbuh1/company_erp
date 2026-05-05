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

        if ($request->filled('date_from'))   $query->whereDate('date', '>=', $request->date_from);
        if ($request->filled('date_to'))     $query->whereDate('date', '<=', $request->date_to);
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

        $offset  = (int) $request->query('offset', 0);
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

        $otByDay = [];
        $totalOt = 0;
        foreach ($otRequests as $ot) {
            $dk = Carbon::parse($ot->start_at)->toDateString();
            if ($dk >= $monthStart->toDateString() && $dk <= $monthEnd->toDateString()) {
                $otByDay[$dk] = ($otByDay[$dk] ?? 0) + $ot->hours;
                $totalOt     += $ot->hours;
            }
        }

        // Leave requests (distribute hours across covered days)
        $leaveRequests = LeaveRequest::whereIn('user_id', $targetUserIds)
            ->where('status', 'approved')
            ->where('start_at', '<=', $monthEnd)
            ->where('end_at',   '>=', $monthStart)
            ->get();

        $leaveByDay = [];
        $totalLeave = 0;
        foreach ($leaveRequests as $leave) {
            $lStart      = Carbon::parse($leave->start_at)->startOfDay();
            $lEnd        = Carbon::parse($leave->end_at)->startOfDay();
            $totalDays   = max(1, $lStart->diffInDays($lEnd) + 1);
            $hoursPerDay = $leave->hours / $totalDays;

            $cursor   = $lStart->copy()->max($monthStart->copy()->startOfDay());
            $clampEnd = $lEnd->copy()->min($monthEnd->copy()->startOfDay());
            while ($cursor->lte($clampEnd)) {
                $dk              = $cursor->toDateString();
                $leaveByDay[$dk] = ($leaveByDay[$dk] ?? 0) + $hoursPerDay;
                $totalLeave     += $hoursPerDay;
                $cursor->addDay();
            }
        }

        $holidayDates = PublicHoliday::getHolidayDates($calStart->copy(), $calEnd->copy());

        return view('time_logs.monthly', compact(
            'selectedUser', 'selectedTeam', 'selectedTeamId',
            'filterUsers', 'filterTeams', 'selectedUserId', 'mode',
            'monthDate', 'monthStart', 'monthEnd', 'calStart', 'calEnd',
            'logsByDay', 'otByDay', 'leaveByDay',
            'totalWork', 'totalOt', 'totalLeave',
            'holidayDates', 'prevMonth', 'nextMonth', 'monthStr'
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
