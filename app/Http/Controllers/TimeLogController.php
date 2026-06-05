<?php

namespace App\Http\Controllers;

use App\Exports\TimeLogExport;
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
use Maatwebsite\Excel\Facades\Excel;

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
            $otQuery = OvertimeRequest::with(['user', 'task', 'project'])->where('status', 'approved');
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

        // _fab=1 means the request came from the floating Time Log button;
        // redirect back to whichever page the user was on.
        if ($request->boolean('_fab')) {
            return redirect()->back()->with('success', 'Đã lưu giờ làm.');
        }

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
        return redirect()->back()->with('success', 'Time log deleted.');
    }

    /**
     * Export Time Logs + OT Requests to Excel (.xlsx), two sheets.
     * Respects the same viewable-scope and optional query filters as index().
     */
    public function export(Request $request)
    {
        $user = auth()->user();
        if (!$user->can('export timesheet')) abort(403);
        $viewableIds = $this->_viewableUserIds($user);

        // Resolve effective user IDs (team or individual filter)
        $userIds = null;
        if ($request->filled('team_id') && ($user->can('view all timesheet') || $user->can('view team timesheet'))) {
            $members = Team::find($request->team_id)?->users()->pluck('users.id')->toArray() ?? [];
            $userIds = $viewableIds !== null ? array_values(array_intersect($members, $viewableIds)) : $members;
        } elseif ($request->filled('user_id') && ($viewableIds === null || count($viewableIds) > 1)) {
            $uid     = (int) $request->user_id;
            $userIds = ($viewableIds === null || in_array($uid, $viewableIds)) ? [$uid] : null;
        }

        // If no explicit user filter was given but we have a viewable scope, use it
        if ($userIds === null && $viewableIds !== null) {
            $userIds = $viewableIds;
        }

        $filters = array_filter([
            'date_from'  => $request->date_from  ?: null,
            'date_to'    => $request->date_to    ?: null,
            'user_ids'   => $userIds,
            'project_id' => $request->project_id ?: null,
            'task_id'    => $request->task_id    ?: null,
        ]);

        $filename = 'timelog_export_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new TimeLogExport(viewableIds: null, filters: $filters),
            $filename
        );
    }

    /**
     * Show Weekly / Day-range View
     */
    public function weekly(Request $request)
    {
        $user        = auth()->user();
        $viewableIds = $this->_viewableUserIds($user);

        // ── Saved preferences ──────────────────────────────────────────────
        $prefs       = $user->preferences()->firstOrCreate(['user_id' => $user->id]);
        $saved       = $prefs->timesheet_weekly_filters ?? [];

        // ── Handle reset ───────────────────────────────────────────────────
        if ($request->boolean('reset')) {
            $prefs->update(['timesheet_weekly_filters' => null]);
            return redirect()->route('timesheets.timeline');
        }

        // Whether the user explicitly submitted the filter form
        $hasExplicit = $request->has('ts_from');

        // ── Date range ─────────────────────────────────────────────────────
        $tsFrom = $hasExplicit ? $request->query('ts_from') : ($saved['ts_from'] ?? null);
        $tsTo   = $hasExplicit ? $request->query('ts_to')   : ($saved['ts_to']   ?? null);

        if ($tsFrom) {
            $weekStart = Carbon::parse($tsFrom)->startOfDay();
            $weekEnd   = $tsTo ? Carbon::parse($tsTo)->endOfDay() : $weekStart->copy()->addDays(6);
            if ($weekStart->gt($weekEnd)) $weekEnd = $weekStart->copy()->addDays(6);
            if ($weekStart->diffInDays($weekEnd) > 90) $weekEnd = $weekStart->copy()->addDays(90);
            $tsFrom = $weekStart->format('Y-m-d');
            $tsTo   = $weekEnd->format('Y-m-d');
        } else {
            $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->startOfDay();
            $weekEnd   = Carbon::now()->endOfWeek(Carbon::SUNDAY)->endOfDay();
            $tsFrom    = $weekStart->format('Y-m-d');
            $tsTo      = $weekEnd->format('Y-m-d');
        }

        $days = collect();
        for ($d = $weekStart->copy(); $d->lte($weekEnd); $d->addDay()) {
            $days->push($d->copy());
        }

        // ── User / Team filter lists for dropdowns ─────────────────────────
        $filterUsers = null;
        $filterTeams = null;
        if ($viewableIds === null) {
            $filterUsers = User::orderBy('name')->get(['id', 'name', 'position']);
            $filterTeams = Team::orderBy('name')->get(['id', 'name']);
        } elseif (count($viewableIds) > 1) {
            $filterUsers = User::whereIn('id', $viewableIds)->orderBy('name')->get(['id', 'name', 'position']);
            if ($user->can('view team timesheet')) {
                $filterTeams = $user->teams()->get(['teams.id', 'teams.name']);
            }
        }

        // ── Resolve selected user/team IDs ─────────────────────────────────
        $toInts = fn(mixed $v) => array_values(array_map('intval', array_filter((array) $v)));

        $selectedUserIds = $hasExplicit
            ? $toInts($request->query('user_ids', []))
            : $toInts($saved['user_ids'] ?? []);
        $selectedTeamIds = $hasExplicit
            ? $toInts($request->query('team_ids', []))
            : $toInts($saved['team_ids'] ?? []);

        if ($viewableIds !== null && !empty($selectedUserIds)) {
            $selectedUserIds = array_values(array_intersect($selectedUserIds, $viewableIds));
        }

        // Resolve actual user IDs to query (union of selected users + team members)
        $queryUserIds = $selectedUserIds;
        foreach ($selectedTeamIds as $tid) {
            $members = Team::find($tid)?->users()->pluck('users.id')->toArray() ?? [];
            if ($viewableIds !== null) {
                $members = array_values(array_intersect($members, $viewableIds));
            }
            $queryUserIds = array_merge($queryUserIds, $members);
        }
        $queryUserIds = array_values(array_unique($queryUserIds));

        if (empty($queryUserIds)) {
            $queryUserIds    = [$user->id];
            $selectedUserIds = [$user->id];
        }

        // ── Project / Task filters ─────────────────────────────────────────
        $filterProjectIds = $hasExplicit
            ? $toInts($request->query('project_ids', []))
            : $toInts($saved['project_ids'] ?? []);
        $filterTaskIds    = $hasExplicit
            ? $toInts($request->query('task_ids', []))
            : $toInts($saved['task_ids'] ?? []);

        // ── Group view flags ───────────────────────────────────────────────
        $showContext = ($hasExplicit
            ? $request->query('show_context', '1')
            : ($saved['show_context'] ?? '1')) !== '0';
        $showUser    = ($hasExplicit
            ? $request->query('show_user', '1')
            : ($saved['show_user'] ?? '1')) !== '0';
        $showProject = ($hasExplicit
            ? $request->query('show_project', '1')
            : ($saved['show_project'] ?? '1')) !== '0';

        // Dropdown options
        $availableProjects = Project::orderBy('name')->get(['id', 'name']);
        $availableTasks    = Task::orderBy('name')->get(['id', 'name']);

        // ── Build the log query ────────────────────────────────────────────
        $logsQuery = TimeLog::whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->with(['project', 'task', 'user'])
            ->orderBy('date');

        if (count($queryUserIds) === 1) {
            $logsQuery->where('user_id', $queryUserIds[0]);
        } else {
            $logsQuery->whereIn('user_id', $queryUserIds);
        }
        if (!empty($filterProjectIds)) $logsQuery->whereIn('project_id', $filterProjectIds);
        if (!empty($filterTaskIds))    $logsQuery->whereIn('task_id',    $filterTaskIds);

        $logs = $logsQuery->get();

        // ── Build ALL three row groupings ──────────────────────────────────
        $rowsByContext = [];
        $rowsByProject = [];
        $rowsByUser    = [];

        foreach ($logs as $log) {
            $dayKey = $log->date->format('Y-m-d');

            // — By Context (task / project-only / other) —
            if ($log->task_id) {
                $cKey  = 'task_' . $log->task_id;
                $label = 'TK-' . $log->task_id . ($log->task ? ' · ' . $log->task->name : ' (deleted)');
                $link  = $log->task ? route('tasks.show', $log->task_id) : null;
                $cType = 'task';
            } elseif ($log->project_id) {
                $cKey  = 'project_' . $log->project_id;
                $label = 'PJ-' . $log->project_id . ($log->project ? ' · ' . $log->project->name : ' (deleted)');
                $link  = $log->project ? route('projects.show', $log->project_id) : null;
                $cType = 'project';
            } else {
                $cKey  = 'other';
                $label = 'Other';
                $link  = null;
                $cType = 'other';
            }
            if (!isset($rowsByContext[$cKey])) {
                $rowsByContext[$cKey] = ['type' => $cType, 'label' => $label, 'link' => $link,
                    'total' => 0, 'days' => [], 'project_id' => $log->project_id, 'task_id' => $log->task_id];
            }
            if (!isset($rowsByContext[$cKey]['days'][$dayKey])) {
                $rowsByContext[$cKey]['days'][$dayKey] = ['total' => 0, 'descriptions' => []];
            }
            $rowsByContext[$cKey]['days'][$dayKey]['total']         += $log->time_spent;
            $rowsByContext[$cKey]['days'][$dayKey]['descriptions'][] = $log->description ?? '';
            $rowsByContext[$cKey]['total']                          += $log->time_spent;

            // — By Project —
            if ($log->project_id) {
                $pKey   = 'project_' . $log->project_id;
                $pLabel = 'PJ-' . $log->project_id . ($log->project ? ' · ' . $log->project->name : ' (deleted)');
                $pLink  = $log->project ? route('projects.show', $log->project_id) : null;
            } else {
                $pKey   = 'no_project';
                $pLabel = '(Không có dự án)';
                $pLink  = null;
            }
            if (!isset($rowsByProject[$pKey])) {
                $rowsByProject[$pKey] = ['label' => $pLabel, 'link' => $pLink,
                    'total' => 0, 'days' => [], 'project_id' => $log->project_id];
            }
            if (!isset($rowsByProject[$pKey]['days'][$dayKey])) {
                $rowsByProject[$pKey]['days'][$dayKey] = ['total' => 0, 'descriptions' => []];
            }
            $rowsByProject[$pKey]['days'][$dayKey]['total']         += $log->time_spent;
            $rowsByProject[$pKey]['days'][$dayKey]['descriptions'][] = $log->description ?? '';
            $rowsByProject[$pKey]['total']                          += $log->time_spent;

            // — By User —
            $uKey = 'user_' . $log->user_id;
            if (!isset($rowsByUser[$uKey])) {
                $rowsByUser[$uKey] = ['label' => $log->user?->name ?? 'Unknown',
                    'link'    => $log->user_id ? route('users.show', $log->user_id) : null,
                    'total'   => 0, 'days' => [], 'user_id' => $log->user_id];
            }
            if (!isset($rowsByUser[$uKey]['days'][$dayKey])) {
                $rowsByUser[$uKey]['days'][$dayKey] = ['total' => 0, 'descriptions' => []];
            }
            $rowsByUser[$uKey]['days'][$dayKey]['total']         += $log->time_spent;
            $rowsByUser[$uKey]['days'][$dayKey]['descriptions'][] = $log->description ?? '';
            $rowsByUser[$uKey]['total']                          += $log->time_spent;
        }

        uasort($rowsByContext, fn($a, $b) =>
            (['task' => 0, 'project' => 1, 'other' => 2][$a['type']] ?? 3)
            <=> (['task' => 0, 'project' => 1, 'other' => 2][$b['type']] ?? 3));
        // Projects: named projects first (alphabetically), then no_project
        uasort($rowsByProject, fn($a, $b) =>
            ($a['project_id'] ? 0 : 1) <=> ($b['project_id'] ? 0 : 1)
            ?: strcmp($a['label'], $b['label']));
        uasort($rowsByUser, fn($a, $b) => strcmp($a['label'], $b['label']));

        // ── Day totals ─────────────────────────────────────────────────────
        $dayTotals = [];
        foreach ($days as $day) { $dayTotals[$day->format('Y-m-d')] = 0; }
        foreach ($logs as $log) {
            $dk = $log->date->format('Y-m-d');
            if (array_key_exists($dk, $dayTotals)) $dayTotals[$dk] += $log->time_spent;
        }

        $weekTotal   = $logs->sum('time_spent');
        $holidayDates = PublicHoliday::getHolidayDates($weekStart->copy(), $weekEnd->copy());
        $isMultiUser = $filterUsers !== null;

        // ── Approved leave hours per user per day (used by "By User" section) ──
        // Structure: [ user_id => [ 'Y-m-d' => [ ['hours'=>X,'type'=>'...'], ... ] ] ]
        $leaveHoursByUserDay = [];
        if ($isMultiUser) {
            $leavesForView = LeaveRequest::whereIn('user_id', $queryUserIds)
                ->where('status', 'approved')
                ->where('start_at', '<=', $weekEnd->copy()->endOfDay()->toDateTimeString())
                ->where('end_at',   '>=', $weekStart->copy()->startOfDay()->toDateTimeString())
                ->get();

            foreach ($leavesForView as $leave) {
                $lStart    = Carbon::parse($leave->start_at)->startOfDay();
                $lEnd      = Carbon::parse($leave->end_at)->startOfDay();
                $totalDays = max(1, $lStart->diffInDays($lEnd) + 1);
                $hpd       = $leave->hours / $totalDays;

                $cursor   = $lStart->copy()->max($weekStart->copy()->startOfDay());
                $clampEnd = $lEnd->copy()->min($weekEnd->copy()->startOfDay());
                while ($cursor->lte($clampEnd)) {
                    $dk = $cursor->toDateString();
                    $leaveHoursByUserDay[$leave->user_id][$dk][] = [
                        'hours' => $hpd,
                        'type'  => $leave->type,
                    ];
                    $cursor->addDay();
                }
            }
        }

        // ── Approved OT hours mapped by context / project / user ─────────
        // Used by all three group-view sections as an overlay on day cells.
        $approvedOts = OvertimeRequest::whereIn('user_id', $queryUserIds)
            ->where('status', 'approved')
            ->where('start_at', '<=', $weekEnd->copy()->endOfDay()->toDateTimeString())
            ->where('end_at',   '>=', $weekStart->copy()->startOfDay()->toDateTimeString())
            ->get();

        // [contextKey][date] = total hours  (contextKey = 'task_X' | 'project_X' | 'other')
        $otHoursByContextDay = [];
        // [projectKey][date] = total hours  (projectKey = 'project_X' | 'no_project')
        $otHoursByProjectDay = [];
        // [user_id][date] = [['hours'=>X, 'type'=>'...']]
        $otHoursByUserDay    = [];

        foreach ($approvedOts as $ot) {
            $dk = Carbon::parse($ot->start_at)->toDateString();
            if ($dk < $weekStart->toDateString() || $dk > $weekEnd->toDateString()) continue;

            // Context key
            if ($ot->task_id)        $cKey = 'task_'    . $ot->task_id;
            elseif ($ot->project_id) $cKey = 'project_' . $ot->project_id;
            else                     $cKey = 'other';
            $otHoursByContextDay[$cKey][$dk] = ($otHoursByContextDay[$cKey][$dk] ?? 0) + $ot->hours;

            // Project key
            $pKey = $ot->project_id ? 'project_' . $ot->project_id : 'no_project';
            $otHoursByProjectDay[$pKey][$dk] = ($otHoursByProjectDay[$pKey][$dk] ?? 0) + $ot->hours;

            // Per user
            $otHoursByUserDay[$ot->user_id][$dk][] = ['hours' => $ot->hours, 'type' => $ot->type];
        }

        // ── View-layer toggles (NT / Leaves / OT) ─────────────────────────
        $showNT     = ($hasExplicit ? $request->query('show_nt',     '1') : ($saved['show_nt']     ?? '1')) !== '0';
        $showLeaves = ($hasExplicit ? $request->query('show_leaves', '1') : ($saved['show_leaves'] ?? '1')) !== '0';
        $showOT     = ($hasExplicit ? $request->query('show_ot',     '1') : ($saved['show_ot']     ?? '1')) !== '0';

        // ── Persist current filters ────────────────────────────────────────
        $prefs->update(['timesheet_weekly_filters' => [
            'ts_from'      => $tsFrom,
            'ts_to'        => $tsTo,
            'user_ids'     => $selectedUserIds,
            'team_ids'     => $selectedTeamIds,
            'project_ids'  => $filterProjectIds,
            'task_ids'     => $filterTaskIds,
            'show_context' => $showContext ? '1' : '0',
            'show_user'    => $showUser    ? '1' : '0',
            'show_project' => $showProject ? '1' : '0',
            'show_nt'      => $showNT      ? '1' : '0',
            'show_leaves'  => $showLeaves  ? '1' : '0',
            'show_ot'      => $showOT      ? '1' : '0',
        ]]);

        return view('time_logs.weekly', compact(
            'days', 'rowsByContext', 'rowsByProject', 'rowsByUser',
            'weekStart', 'weekEnd', 'tsFrom', 'tsTo',
            'dayTotals', 'weekTotal',
            'filterUsers', 'filterTeams',
            'selectedUserIds', 'selectedTeamIds',
            'filterProjectIds', 'filterTaskIds',
            'availableProjects', 'availableTasks',
            'holidayDates', 'isMultiUser',
            'showContext', 'showUser', 'showProject',
            'leaveHoursByUserDay',
            'otHoursByContextDay', 'otHoursByProjectDay', 'otHoursByUserDay',
            'showNT', 'showLeaves', 'showOT'
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
        $user = auth()->user();
        if (!$user->canAny(['view project timesheet', 'view all timesheet'])) abort(403);
        $viewableIds = $this->_viewableUserIds($user);

        // ── Date range (default = current week) ───────────────────────────
        $fromDate = $request->query('from_date', now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d'));
        $toDate   = $request->query('to_date',   now()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d'));
        $start    = Carbon::parse($fromDate)->startOfDay();
        $end      = Carbon::parse($toDate)->endOfDay();
        if ($start->gt($end)) $end = $start->copy()->addDays(6);
        if ($start->diffInDays($end) > 90) $end = $start->copy()->addDays(90);
        $fromDate = $start->format('Y-m-d');
        $toDate   = $end->format('Y-m-d');

        $days = collect();
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $days->push($d->copy());
        }

        // ── Filters ────────────────────────────────────────────────────────
        $toInts = fn(mixed $v) => array_values(array_map('intval', array_filter((array) $v)));

        $filterProjectIds = $toInts($request->query('project_ids', []));
        $filterTaskIds    = $toInts($request->query('task_ids',    []));
        $filterUserIds    = $toInts($request->query('user_ids',    []));

        // Determine effective user IDs for queries
        if ($viewableIds === null) {
            $effectiveUserIds = empty($filterUserIds) ? null : $filterUserIds;
        } else {
            $effectiveUserIds = empty($filterUserIds)
                ? $viewableIds
                : array_values(array_intersect($filterUserIds, $viewableIds));
        }

        // ── Filter dropdown options ────────────────────────────────────────
        $availableProjects = Project::orderBy('name')->get(['id', 'name']);
        $availableTasks    = Task::whereNotNull('project_id')->orderBy('name')->get(['id', 'name', 'project_id']);
        $availableUsers    = null;
        if ($viewableIds === null) {
            $availableUsers = User::orderBy('name')->get(['id', 'name', 'position']);
        } elseif (count($viewableIds) > 1) {
            $availableUsers = User::whereIn('id', $viewableIds)->orderBy('name')->get(['id', 'name', 'position']);
        }

        // ── Query time logs ────────────────────────────────────────────────
        $logsQuery = TimeLog::whereBetween('date', [$fromDate, $toDate])
            ->whereNotNull('project_id')
            ->with(['user', 'task', 'project']);

        if ($effectiveUserIds !== null) $logsQuery->whereIn('user_id', $effectiveUserIds);
        if (!empty($filterProjectIds))  $logsQuery->whereIn('project_id', $filterProjectIds);
        if (!empty($filterTaskIds))     $logsQuery->whereIn('task_id',    $filterTaskIds);

        $timeLogs = $logsQuery->get();

        // ── Query approved OT ──────────────────────────────────────────────
        $otQuery = OvertimeRequest::where('status', 'approved')
            ->whereDate('start_at', '>=', $fromDate)
            ->whereDate('start_at', '<=', $toDate)
            ->whereNotNull('project_id')
            ->with(['user', 'task', 'project']);

        if ($effectiveUserIds !== null) $otQuery->whereIn('user_id', $effectiveUserIds);
        if (!empty($filterProjectIds))  $otQuery->whereIn('project_id', $filterProjectIds);
        if (!empty($filterTaskIds))     $otQuery->whereIn('task_id',    $filterTaskIds);

        $otRequests = $otQuery->get();

        // ── Build nested structure: Project → Task → User ─────────────────
        // Structure:
        //   $projectGroups[$pk] = [project_id, project, total_hours, total_ot, days[], tasks[
        //     $tk => [task_id, task, total_hours, total_ot, days[], users[
        //       $uk => [user_id, user, total_hours, total_ot, days[]]
        //     ]]
        //   ]]
        $projectGroups = [];

        $bump = function (array &$node, string $dk, float $hours, float $ot) {
            $node['total_hours'] += $hours;
            $node['total_ot']    += $ot;
            $node['days'][$dk]['hours']    = ($node['days'][$dk]['hours']    ?? 0) + $hours;
            $node['days'][$dk]['ot_hours'] = ($node['days'][$dk]['ot_hours'] ?? 0) + $ot;
        };

        foreach ($timeLogs as $log) {
            $dk = $log->date->format('Y-m-d');
            $pk = 'p_'  . $log->project_id;
            $tk = 'tk_' . ($log->task_id ?? 'none');
            $uk = 'u_'  . $log->user_id;

            if (!isset($projectGroups[$pk])) {
                $projectGroups[$pk] = ['project_id' => $log->project_id, 'project' => $log->project,
                    'total_hours' => 0, 'total_ot' => 0, 'days' => [], 'tasks' => []];
            }
            if (!isset($projectGroups[$pk]['tasks'][$tk])) {
                $projectGroups[$pk]['tasks'][$tk] = ['task_id' => $log->task_id, 'task' => $log->task,
                    'total_hours' => 0, 'total_ot' => 0, 'days' => [], 'users' => []];
            }
            if (!isset($projectGroups[$pk]['tasks'][$tk]['users'][$uk])) {
                $projectGroups[$pk]['tasks'][$tk]['users'][$uk] = ['user_id' => $log->user_id, 'user' => $log->user,
                    'total_hours' => 0, 'total_ot' => 0, 'days' => []];
            }

            $bump($projectGroups[$pk], $dk, $log->time_spent, 0);
            $bump($projectGroups[$pk]['tasks'][$tk], $dk, $log->time_spent, 0);
            $bump($projectGroups[$pk]['tasks'][$tk]['users'][$uk], $dk, $log->time_spent, 0);
        }

        foreach ($otRequests as $ot) {
            $dk = Carbon::parse($ot->start_at)->format('Y-m-d');
            if ($dk < $fromDate || $dk > $toDate) continue;
            $pk = 'p_'  . $ot->project_id;
            $tk = 'tk_' . ($ot->task_id ?? 'none');
            $uk = 'u_'  . $ot->user_id;

            if (!isset($projectGroups[$pk])) {
                $projectGroups[$pk] = ['project_id' => $ot->project_id, 'project' => $ot->project,
                    'total_hours' => 0, 'total_ot' => 0, 'days' => [], 'tasks' => []];
            }
            if (!isset($projectGroups[$pk]['tasks'][$tk])) {
                $projectGroups[$pk]['tasks'][$tk] = ['task_id' => $ot->task_id, 'task' => $ot->task,
                    'total_hours' => 0, 'total_ot' => 0, 'days' => [], 'users' => []];
            }
            if (!isset($projectGroups[$pk]['tasks'][$tk]['users'][$uk])) {
                $projectGroups[$pk]['tasks'][$tk]['users'][$uk] = ['user_id' => $ot->user_id, 'user' => $ot->user,
                    'total_hours' => 0, 'total_ot' => 0, 'days' => []];
            }

            $bump($projectGroups[$pk], $dk, 0, $ot->hours);
            $bump($projectGroups[$pk]['tasks'][$tk], $dk, 0, $ot->hours);
            $bump($projectGroups[$pk]['tasks'][$tk]['users'][$uk], $dk, 0, $ot->hours);
        }

        // Sort: projects & tasks alphabetically (named first), users alphabetically
        uasort($projectGroups, fn($a, $b) => strcmp($a['project']?->name ?? '', $b['project']?->name ?? ''));
        foreach ($projectGroups as &$pg) {
            uasort($pg['tasks'], fn($a, $b) =>
                ($a['task_id'] ? 0 : 1) <=> ($b['task_id'] ? 0 : 1)
                ?: strcmp($a['task']?->name ?? '', $b['task']?->name ?? ''));
            foreach ($pg['tasks'] as &$tg) {
                uasort($tg['users'], fn($a, $b) => strcmp($a['user']?->name ?? '', $b['user']?->name ?? ''));
            }
            unset($tg);
        }
        unset($pg);

        // ── Day totals ─────────────────────────────────────────────────────
        $dayTotals = [];
        foreach ($days as $day) {
            $dk = $day->format('Y-m-d');
            $dayTotals[$dk] = ['hours' => 0, 'ot_hours' => 0];
            foreach ($projectGroups as $pg) {
                $dayTotals[$dk]['hours']    += $pg['days'][$dk]['hours']    ?? 0;
                $dayTotals[$dk]['ot_hours'] += $pg['days'][$dk]['ot_hours'] ?? 0;
            }
        }

        $grandTotalHours = (float) collect($dayTotals)->sum('hours');
        $grandTotalOt    = (float) collect($dayTotals)->sum('ot_hours');
        $holidayDates    = PublicHoliday::getHolidayDates($start->copy(), $end->copy());

        // Alpine initial open state — all expanded by default
        $initOpenProjects = [];
        $initOpenTasks    = [];
        foreach ($projectGroups as $pk => $pg) {
            $initOpenProjects[$pk] = true;
            foreach ($pg['tasks'] as $tk => $tg) {
                $initOpenTasks[$pk . '_' . $tk] = true;
            }
        }

        // Can this user see other people's per-user rows?
        $canViewOthers = $user->can('view all timesheet')
            || $user->can('view team timesheet')
            || $user->teams()->where('team_user.is_leader', true)->exists();

        return view('time_logs.project', compact(
            'days', 'fromDate', 'toDate',
            'projectGroups', 'dayTotals',
            'grandTotalHours', 'grandTotalOt',
            'availableProjects', 'availableTasks', 'availableUsers',
            'filterProjectIds', 'filterTaskIds', 'filterUserIds',
            'holidayDates',
            'initOpenProjects', 'initOpenTasks',
            'canViewOthers'
        ));
    }

    /**
     * Attendance timesheet — user × day grid showing work / leave / OT hours
     * with color-coded cells based on daily coverage.
     */
    public function attendanceView(Request $request)
    {
        $user = auth()->user();
        if (!$user->canAny(['view attendance timesheet', 'view all timesheet'])) abort(403);
        $viewableIds = $this->_viewableUserIds($user);

        // ── Date range (default = current month) ──────────────────────────
        $fromDate = $request->query('from_date', now()->startOfMonth()->format('Y-m-d'));
        $toDate   = $request->query('to_date',   now()->endOfMonth()->format('Y-m-d'));
        $start    = Carbon::parse($fromDate)->startOfDay();
        $end      = Carbon::parse($toDate)->endOfDay();
        if ($start->gt($end))              $end = $start->copy()->addDays(30);
        if ($start->diffInDays($end) > 90) $end = $start->copy()->addDays(90);
        $fromDate = $start->format('Y-m-d');
        $toDate   = $end->format('Y-m-d');

        $days = collect();
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $days->push($d->copy());
        }

        // ── Filters ────────────────────────────────────────────────────────
        $toInts        = fn(mixed $v) => array_values(array_map('intval', array_filter((array) $v)));
        $filterTeamIds = $toInts($request->query('team_ids', []));
        $filterUserIds = $toInts($request->query('user_ids', []));

        // ── Dropdown options ───────────────────────────────────────────────
        if ($viewableIds === null) {
            $availableTeams = Team::orderBy('name')->get(['id', 'name']);
            $availableUsers = User::orderBy('name')->get(['id', 'name', 'position']);
        } elseif (count($viewableIds) > 1) {
            $availableTeams = Team::whereHas('users', fn ($q) => $q->whereIn('users.id', $viewableIds))
                ->orderBy('name')->get(['id', 'name']);
            $availableUsers = User::whereIn('id', $viewableIds)->orderBy('name')
                ->get(['id', 'name', 'position']);
        } else {
            $availableTeams = collect();
            $availableUsers = collect();
        }

        // ── Resolve effective member IDs ───────────────────────────────────
        $effectiveIds = $viewableIds; // null = unrestricted

        if (!empty($filterTeamIds)) {
            $teamMemberIds = [];
            foreach ($filterTeamIds as $tid) {
                $ms = Team::find($tid)?->users()->pluck('users.id')->toArray() ?? [];
                $teamMemberIds = array_merge($teamMemberIds, $ms);
            }
            $teamMemberIds = array_unique($teamMemberIds);
            if ($effectiveIds !== null) {
                $teamMemberIds = array_values(array_intersect($teamMemberIds, $effectiveIds));
            }
            $effectiveIds = $teamMemberIds;
        }

        if (!empty($filterUserIds)) {
            $effectiveIds = $effectiveIds !== null
                ? array_values(array_intersect($filterUserIds, $effectiveIds))
                : $filterUserIds;
        }

        // ── Members ────────────────────────────────────────────────────────
        $membersQuery = User::orderBy('name');
        if ($effectiveIds !== null) {
            $membersQuery->whereIn('id', !empty($effectiveIds) ? $effectiveIds : [-1]);
        }
        $members   = $membersQuery->get();
        $memberIds = $members->pluck('id')->toArray();

        // ── Time logs: [user_id][date] = hours ─────────────────────────────
        $tlByUserDay = [];
        if (!empty($memberIds)) {
            foreach (
                TimeLog::whereIn('user_id', $memberIds)
                    ->whereBetween('date', [$fromDate, $toDate])
                    ->get(['user_id', 'date', 'time_spent']) as $log
            ) {
                $dk = $log->date->format('Y-m-d');
                $tlByUserDay[$log->user_id][$dk] = ($tlByUserDay[$log->user_id][$dk] ?? 0) + $log->time_spent;
            }
        }

        // ── Approved leaves: [user_id][date] = prorated hours ─────────────
        $lvByUserDay = [];
        if (!empty($memberIds)) {
            foreach (
                LeaveRequest::where('status', 'approved')
                    ->whereIn('user_id', $memberIds)
                    ->where('start_at', '<=', $end->toDateTimeString())
                    ->where('end_at',   '>=', $start->toDateTimeString())
                    ->get(['user_id', 'start_at', 'end_at', 'hours']) as $leave
            ) {
                $lS  = Carbon::parse($leave->start_at)->startOfDay();
                $lE  = Carbon::parse($leave->end_at)->startOfDay();
                $hpd = $leave->hours / max(1, $lS->diffInDays($lE) + 1);
                $cur = $lS->copy()->max($start->copy()->startOfDay());
                $cap = $lE->copy()->min($end->copy()->startOfDay());
                while ($cur->lte($cap)) {
                    $dk = $cur->toDateString();
                    $lvByUserDay[$leave->user_id][$dk] = ($lvByUserDay[$leave->user_id][$dk] ?? 0) + $hpd;
                    $cur->addDay();
                }
            }
        }

        // ── Approved OT: [user_id][date] = hours ──────────────────────────
        $otByUserDay = [];
        if (!empty($memberIds)) {
            foreach (
                OvertimeRequest::where('status', 'approved')
                    ->whereIn('user_id', $memberIds)
                    ->whereDate('start_at', '>=', $fromDate)
                    ->whereDate('start_at', '<=', $toDate)
                    ->get(['user_id', 'start_at', 'hours']) as $ot
            ) {
                $dk = Carbon::parse($ot->start_at)->toDateString();
                $otByUserDay[$ot->user_id][$dk] = ($otByUserDay[$ot->user_id][$dk] ?? 0) + $ot->hours;
            }
        }

        $holidayDates = PublicHoliday::getHolidayDates($start->copy(), $end->copy());
        $today        = now()->toDateString();

        return view('time_logs.attendance', compact(
            'members', 'days', 'fromDate', 'toDate',
            'tlByUserDay', 'lvByUserDay', 'otByUserDay',
            'availableTeams', 'availableUsers',
            'filterTeamIds', 'filterUserIds',
            'holidayDates', 'today'
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
