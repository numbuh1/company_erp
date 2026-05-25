<?php
namespace App\Http\Controllers;

use App\Models\OvertimeRequest;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\PublicHoliday;
use App\Models\Task;
use App\Models\Team;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user  = auth()->user();
        $query = Project::with(['teams.users', 'users']);
        $this->_scopeQuery($query, $user);
        $projects = $query->latest()->paginate(10);

        // Compute time spent per project from time_logs
        $projIds            = $projects->pluck('id')->toArray();
        $projectTimeSpentMap = TimeLog::whereIn('project_id', $projIds)
            ->groupBy('project_id')
            ->selectRaw('project_id, SUM(time_spent) as total')
            ->pluck('total', 'project_id')
            ->map(fn($v) => (float) $v);

        return view('projects.index', compact('projects', 'projectTimeSpentMap'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!auth()->user()->can('edit projects')) abort(403);
        $teams = Team::with('users')->orderBy('name')->get();
        return view('projects.form', [
            'teams'       => $teams,
            'users'       => User::orderBy('name')->get(),
            'teamMembers' => $teams->mapWithKeys(fn($t) => [
                $t->id => $t->users->pluck('id')->toArray()
            ]),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('edit projects')) abort(403);

        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'description'       => 'nullable|string',
            'start_date'        => 'nullable|date',
            'expected_end_date' => 'nullable|date',
            'actual_end_date'   => 'nullable|date',
            'teams'             => 'nullable|array',
            'members'           => 'nullable|array',
            'status'            => 'nullable|string',
            'budget_hours'      => 'nullable|numeric|min:0',
        ]);

        $project = Project::create($data);
        $project->teams()->sync($request->teams ?? []);
        $project->users()->sync($request->members ?? []);

        return redirect()->route('projects.show', $project)->with('success', 'Project created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Project $project)
    {
        $user = auth()->user();
        if (!$user->can('view all projects') && !$this->_isAssigned($project, $user)) abort(403);

        $project->load(['teams.users', 'users', 'comments.user']);

        // File explorer: current folder
        $folderId     = $request->query('folder_id');
        $currentFolder = $folderId ? ProjectFile::findOrFail($folderId) : null;
        if ($currentFolder && $currentFolder->project_id !== $project->id) abort(404);

        // Items in current folder
        $items = ProjectFile::where('project_id', $project->id)
            ->where('parent_id', $folderId)
            ->orderByDesc('is_folder')
            ->orderBy('name')
            ->orderBy('original_name')
            ->get();

        // Build breadcrumb by walking up parent chain
        $breadcrumb = [];
        $folder = $currentFolder;
        while ($folder) {
            array_unshift($breadcrumb, $folder);
            $folder = $folder->parent_id ? ProjectFile::find($folder->parent_id) : null;
        }

        // Activity log
        $activities = Activity::where('subject_type', Project::class)
            ->where('subject_id', $project->id)
            ->with('causer')
            ->latest()
            ->get();

        $canUpload    = $user->can('edit all project files') || $user->can('edit own project files');
        $canManageAll = $user->can('edit all project files');

        // ── Task tab filters ──────────────────────────────────────────────────
        $taskSearch     = trim($request->input('search', ''));
        $taskAssigneeId = $request->input('assignee_id', '');
        $taskSort       = $request->input('sort', 'id_asc');

        $taskQuery = $project->tasks()->with('assignees');

        if ($taskSearch) {
            $taskQuery->where(function ($q) use ($taskSearch) {
                $q->where('name', 'like', "%{$taskSearch}%");
                $numId = (int) preg_replace('/[^0-9]/', '', $taskSearch);
                if ($numId > 0) {
                    $q->orWhere('id', $numId);
                }
            });
        }
        if ($taskAssigneeId) {
            $taskQuery->whereHas('assignees', fn ($q) => $q->where('users.id', (int) $taskAssigneeId));
        }
        match ($taskSort) {
            'id_desc' => $taskQuery->orderBy('id', 'desc'),
            'due_asc' => $taskQuery->orderBy('expected_end_date', 'asc'),
            'due_desc'=> $taskQuery->orderBy('expected_end_date', 'desc'),
            default   => $taskQuery->orderBy('id', 'asc'),
        };
        $projectTasks = $taskQuery->get();

        // Compute time spent per task
        $ptaskIds        = $projectTasks->pluck('id')->toArray();
        $taskTimeSpentMap = TimeLog::whereIn('task_id', $ptaskIds)
            ->groupBy('task_id')
            ->selectRaw('task_id, SUM(time_spent) as total')
            ->pluck('total', 'task_id')
            ->map(fn($v) => (float) $v);

        // Project-level budget stats
        $projectTotalSpent = (float) TimeLog::where('project_id', $project->id)->sum('time_spent');
        $projectTotalOt    = (float) OvertimeRequest::where('project_id', $project->id)
            ->where('status', 'approved')
            ->sum('hours');
        $projectRemaining  = $project->budget_hours !== null
            ? $project->budget_hours - $projectTotalSpent - $projectTotalOt
            : null;

        // Users who are assigned to any task in this project (for the filter dropdown)
        $taskAssignees = User::whereIn('id', function ($q) use ($project) {
            $q->select('task_user.user_id')
              ->from('task_user')
              ->join('tasks', 'task_user.task_id', '=', 'tasks.id')
              ->where('tasks.project_id', $project->id)
              ->whereNull('tasks.deleted_at')
              ->distinct();
        })->orderBy('name')->get();

        // ── Timesheet tab data ────────────────────────────────────────────────
        // Completed project → auto-range to last 30 days of recorded activity
        $isCompletedProject = in_array($project->status, ['Đã xong', 'Done']);
        $lastProjLogDate    = TimeLog::where('project_id', $project->id)->max('date');
        if (!$request->has('ts_from') && $isCompletedProject && $lastProjLogDate) {
            $tsRangeEnd   = Carbon::parse($lastProjLogDate);
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

        // Determine viewable user IDs for the timesheet scope
        $tsViewableIds = null;
        if (!$user->can('view all timesheet')) {
            if ($user->can('view team timesheet')) {
                $teamUserIds = $user->teamMembers()->pluck('id')->toArray();
                $tsViewableIds = array_unique(array_merge([$user->id], $teamUserIds));
            } elseif ($user->can('view own timesheet')) {
                $tsViewableIds = [$user->id];
            } else {
                $tsViewableIds = [$user->id]; // fallback: own only
            }
        }

        // All user IDs for this project (direct + via teams), intersected with viewable scope
        $projectUserIds    = $project->users->pluck('id')->toArray();
        $teamUserIds       = $project->teams->flatMap(fn($t) => $t->users->pluck('id'))->toArray();
        $allProjectUserIds = array_values(array_unique(array_merge($projectUserIds, $teamUserIds)));
        if ($tsViewableIds !== null) {
            $allProjectUserIds = array_values(array_intersect($allProjectUserIds, $tsViewableIds));
        }

        $tsProjectUsers = User::whereIn('id', $allProjectUserIds)->orderBy('name')->get()->keyBy('id');
        $tsProjectTasks = Task::where('project_id', $project->id)->orderBy('name')->get()->keyBy('id');

        // Time logs for this project in the selected range
        $tsTimeLogs = TimeLog::where('project_id', $project->id)
            ->whereBetween('date', [$tsFromStr, $tsToStr])
            ->with(['user', 'task'])
            ->get();

        // Approved OT for this project in the selected range
        $tsOtRequests = OvertimeRequest::where('project_id', $project->id)
            ->where('status', 'approved')
            ->whereDate('start_at', '>=', $tsFromStr)
            ->whereDate('start_at', '<=', $tsToStr)
            ->with('user')
            ->get();

        // Build task rows
        $tsTaskRows = [];
        foreach ($tsTimeLogs as $log) {
            $taskKey = $log->task_id ? 'task_' . $log->task_id : 'no_task';
            $dk      = $log->date->format('Y-m-d');
            $userObj = $tsProjectUsers->get($log->user_id);
            $cost    = (float) ($userObj?->hourly_rate ?? 0) * $log->time_spent;

            if (!isset($tsTaskRows[$taskKey])) {
                $task = $log->task_id ? ($tsProjectTasks->get($log->task_id) ?? $log->task) : null;
                $tsTaskRows[$taskKey] = [
                    'task' => $task, 'task_id' => $log->task_id,
                    'label' => $log->task_id
                        ? 'TK-' . $log->task_id . ($task ? ' · ' . $task->name : '')
                        : '(Không có công việc)',
                    'days' => [], 'total_hours' => 0, 'total_ot' => 0, 'total_cost' => 0, 'total_ot_cost' => 0,
                ];
            }
            if (!isset($tsTaskRows[$taskKey]['days'][$dk])) {
                $tsTaskRows[$taskKey]['days'][$dk] = ['hours' => 0, 'ot_hours' => 0, 'cost' => 0, 'ot_cost' => 0];
            }
            $tsTaskRows[$taskKey]['days'][$dk]['hours'] += $log->time_spent;
            $tsTaskRows[$taskKey]['days'][$dk]['cost']  += $cost;
            $tsTaskRows[$taskKey]['total_hours']        += $log->time_spent;
            $tsTaskRows[$taskKey]['total_cost']         += $cost;
        }

        foreach ($tsOtRequests as $ot) {
            $taskKey    = $ot->task_id ? 'task_' . $ot->task_id : 'no_task';
            $dk         = Carbon::parse($ot->start_at)->format('Y-m-d');
            $multiplier = match ($ot->type) {
                'OT x1.5' => 1.5, 'OT x2' => 2.0, 'OT x3' => 3.0, default => 1.0,
            };
            $userObj = $tsProjectUsers->get($ot->user_id);
            $cost    = (float) ($userObj?->hourly_rate ?? 0) * $ot->hours * $multiplier;

            if (!isset($tsTaskRows[$taskKey])) {
                $task = $ot->task_id ? $tsProjectTasks->get($ot->task_id) : null;
                $tsTaskRows[$taskKey] = [
                    'task' => $task, 'task_id' => $ot->task_id,
                    'label' => $ot->task_id
                        ? 'TK-' . $ot->task_id . ($task ? ' · ' . $task->name : '')
                        : '(Không có công việc)',
                    'days' => [], 'total_hours' => 0, 'total_ot' => 0, 'total_cost' => 0, 'total_ot_cost' => 0,
                ];
            }
            if (!isset($tsTaskRows[$taskKey]['days'][$dk])) {
                $tsTaskRows[$taskKey]['days'][$dk] = ['hours' => 0, 'ot_hours' => 0, 'cost' => 0, 'ot_cost' => 0];
            }
            $tsTaskRows[$taskKey]['days'][$dk]['ot_hours']  += $ot->hours;
            $tsTaskRows[$taskKey]['days'][$dk]['ot_cost']   += $cost;
            $tsTaskRows[$taskKey]['total_ot']               += $ot->hours;
            $tsTaskRows[$taskKey]['total_ot_cost']          += $cost;
        }

        // Build user rows
        $tsUserRows = [];
        foreach ($tsTimeLogs as $log) {
            $userKey = 'user_' . $log->user_id;
            $dk      = $log->date->format('Y-m-d');
            $userObj = $tsProjectUsers->get($log->user_id);
            $cost    = (float) ($userObj?->hourly_rate ?? 0) * $log->time_spent;

            if (!isset($tsUserRows[$userKey])) {
                $tsUserRows[$userKey] = [
                    'user' => $userObj ?? $log->user, 'user_id' => $log->user_id,
                    'days' => [], 'total_hours' => 0, 'total_ot' => 0, 'total_cost' => 0, 'total_ot_cost' => 0,
                ];
            }
            if (!isset($tsUserRows[$userKey]['days'][$dk])) {
                $tsUserRows[$userKey]['days'][$dk] = ['hours' => 0, 'ot_hours' => 0, 'cost' => 0, 'ot_cost' => 0];
            }
            $tsUserRows[$userKey]['days'][$dk]['hours'] += $log->time_spent;
            $tsUserRows[$userKey]['days'][$dk]['cost']  += $cost;
            $tsUserRows[$userKey]['total_hours']        += $log->time_spent;
            $tsUserRows[$userKey]['total_cost']         += $cost;
        }

        foreach ($tsOtRequests as $ot) {
            $userKey    = 'user_' . $ot->user_id;
            $dk         = Carbon::parse($ot->start_at)->format('Y-m-d');
            $multiplier = match ($ot->type) {
                'OT x1.5' => 1.5, 'OT x2' => 2.0, 'OT x3' => 3.0, default => 1.0,
            };
            $userObj = $tsProjectUsers->get($ot->user_id);
            $cost    = (float) ($userObj?->hourly_rate ?? 0) * $ot->hours * $multiplier;

            if (!isset($tsUserRows[$userKey])) {
                $tsUserRows[$userKey] = [
                    'user' => $userObj ?? $ot->user, 'user_id' => $ot->user_id,
                    'days' => [], 'total_hours' => 0, 'total_ot' => 0, 'total_cost' => 0, 'total_ot_cost' => 0,
                ];
            }
            if (!isset($tsUserRows[$userKey]['days'][$dk])) {
                $tsUserRows[$userKey]['days'][$dk] = ['hours' => 0, 'ot_hours' => 0, 'cost' => 0, 'ot_cost' => 0];
            }
            $tsUserRows[$userKey]['days'][$dk]['ot_hours']  += $ot->hours;
            $tsUserRows[$userKey]['days'][$dk]['ot_cost']   += $cost;
            $tsUserRows[$userKey]['total_ot']               += $ot->hours;
            $tsUserRows[$userKey]['total_ot_cost']          += $cost;
        }

        uasort($tsUserRows, fn($a, $b) => strcmp($a['user']?->name ?? '', $b['user']?->name ?? ''));

        // Day totals (from user rows to avoid double-count)
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
        $tsInitialTab    = $request->query('tab', ($request->has('ts_from') || $request->has('ts_to')) ? 'timesheet' : 'tasks');

        // Column preferences for project tasks tab
        $savedTaskCols = $user->preferences?->project_task_column_preferences;
        $taskColPrefs  = json_encode($savedTaskCols ?? [
            'status' => true, 'assignees' => true,
            'budget' => true, 'start_date' => true, 'due_date' => true,
        ]);

        return view('projects.show', compact(
            'project', 'items', 'currentFolder', 'breadcrumb', 'activities', 'canUpload', 'canManageAll',
            'projectTasks', 'taskAssignees', 'taskSearch', 'taskAssigneeId', 'taskSort',
            'taskTimeSpentMap', 'projectTotalSpent', 'projectTotalOt', 'projectRemaining',
            'tsFromStr', 'tsToStr', 'tsDays',
            'tsTaskRows', 'tsUserRows', 'tsDayTotals',
            'tsGrandTotalHours', 'tsGrandTotalOt', 'tsGrandTotalCost', 'tsGrandTotalOtCost',
            'tsHolidayDates', 'tsCanViewSalary', 'tsInitialTab',
            'taskColPrefs'
        ));
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project)
    {
        $user = auth()->user();
        if (!$user->can('edit projects')) {
            if (!$user->can('edit assigned projects') || !$this->_isAssigned($project, $user)) abort(403);
        }

        $project->load(['teams', 'users']);
        $teams = Team::with('users')->orderBy('name')->get();
        return view('projects.form', [
            'project'     => $project,
            'teams'       => $teams,
            'users'       => User::orderBy('name')->get(),
            'teamMembers' => $teams->mapWithKeys(fn($t) => [
                $t->id => $t->users->pluck('id')->toArray()
            ]),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project)
    {
        $user = auth()->user();
        if (!$user->can('edit projects')) {
            if (!$user->can('edit assigned projects') || !$this->_isAssigned($project, $user)) abort(403);
        }

        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'description'       => 'nullable|string',
            'start_date'        => 'nullable|date',
            'expected_end_date' => 'nullable|date',
            'actual_end_date'   => 'nullable|date',
            'teams'             => 'nullable|array',
            'members'           => 'nullable|array',
            'status'            => 'nullable|string',
            'budget_hours'      => 'nullable|numeric|min:0',
        ]);

        $project->update($data);
        $project->teams()->sync($request->teams ?? []);
        $project->users()->sync($request->members ?? []);

        return redirect()->route('projects.show', $project)->with('success', 'Project updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        if (!auth()->user()->can('delete projects')) abort(403);
        $project->delete();
        return redirect()->route('projects.index')->with('success', 'Project deleted.');
    }

    /**
     * Search project
     */
    public function search(Request $request)
    {
        $user  = auth()->user();
        $q     = $request->get('q', '');
        $query = Project::query();
        $this->_scopeQuery($query, $user);

        $projects = $query
            ->where('name', 'like', "%{$q}%")
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name']);

        return response()->json($projects->map(fn($p) => [
            'id'   => $p->id,
            'text' => 'PJ-' . $p->id . ' ' . $p->name,
        ]));
    }

    /**
     * Upload file into a Project
     */
    public function uploadFile(Request $request, Project $project)
    {
        $user = auth()->user();
        if (!$this->_canAccessProject($project, $user)) abort(403);
        if (!$user->can('edit all project files') && !$user->can('edit own project files')) abort(403);

        $request->validate([
            'file'      => 'required|file|max:51200',
            'parent_id' => 'nullable|integer|exists:project_files,id',
        ]);

        $file       = $request->file('file');
        $storedName = basename($file->store("project_files/{$project->id}", 'public'));
        $displayName = $file->getClientOriginalName();

        ProjectFile::create([
            'project_id'    => $project->id,
            'uploaded_by'   => $user->id,
            'is_folder'     => false,
            'parent_id'     => $request->parent_id,
            'name'          => $displayName,
            'original_name' => $displayName,
            'stored_name'   => $storedName,
            'size'          => $file->getSize(),
        ]);

        activity()->causedBy($user)->performedOn($project)
            ->log("Uploaded file \"{$displayName}\"");

        return redirect()->back()->with('success', 'File uploaded.');
    }


    /**
     * Delete file from a Project
     */
    public function deleteItem(Project $project, ProjectFile $file)
    {
        $user = auth()->user();
        if (!$this->_canAccessProject($project, $user)) abort(403);
        if ($user->can('edit all project files')) {
            // allowed — fall through
        } elseif ($user->can('edit own project files')) {
            if ($file->is_folder || $file->uploaded_by !== $user->id) abort(403);
        } else {
            abort(403);
        }


        $itemName = $file->name;
        $itemType = $file->is_folder ? 'folder' : 'file';
        $this->_deleteRecursive($project, $file);

        activity()->causedBy($user)->performedOn($project)
            ->log("Deleted {$itemType} \"{$itemName}\"");

        return back()->with('success', $file->is_folder ? 'Folder deleted.' : 'File deleted.');
    }

    private function _deleteRecursive(Project $project, ProjectFile $item): void
    {
        if ($item->is_folder) {
            foreach (ProjectFile::where('parent_id', $item->id)->get() as $child) {
                $this->_deleteRecursive($project, $child);
            }
        } else {
            Storage::disk('public')->delete("project_files/{$project->id}/{$item->stored_name}");
        }
        $item->delete();
    }


    /**
     * Create folder for a Project
     */
    public function createFolder(Request $request, Project $project)
    {
        $user = auth()->user();
        if (!$this->_canAccessProject($project, $user)) abort(403);

        $request->validate([
            'name'      => 'required|string|max:255',
            'parent_id' => 'nullable|integer|exists:project_files,id',
        ]);

        ProjectFile::create([
            'project_id'  => $project->id,
            'uploaded_by' => $user->id,
            'is_folder'   => true,
            'parent_id'   => $request->parent_id,
            'name'        => $request->name,
        ]);

        return back()->with('success', 'Folder created.');
    }

    /**
     * Rename folder / file for a Project
     */
    public function renameItem(Request $request, Project $project, ProjectFile $file)
    {
        $user = auth()->user();
        if (!$this->_canAccessProject($project, $user)) abort(403);
        if ($user->can('edit all project files')) {
            // allowed — fall through
        } elseif ($user->can('edit own project files')) {
            if ($file->is_folder || $file->uploaded_by !== $user->id) abort(403);
        } else {
            abort(403);
        }


        $request->validate(['name' => 'required|string|max:255']);

        $oldName = $file->name;
        $file->update(['name' => $request->name]);

        activity()->causedBy($user)->performedOn($project)
            ->log("Renamed \"{$oldName}\" to \"{$request->name}\"");

        return back()->with('success', 'Renamed successfully.');
    }

    /**
     * Download a file or folder (as ZIP) from a Project
     */
    public function downloadItem(Project $project, ProjectFile $file)
    {
        $user = auth()->user();
        if (!$user->can('view all projects') && !$this->_isAssigned($project, $user)) abort(403);
        if ($file->project_id !== $project->id) abort(404);

        if (!$file->is_folder) {
            $path = "project_files/{$project->id}/{$file->stored_name}";
            if (!Storage::disk('public')->exists($path)) abort(404);
            return Storage::disk('public')->download($path, $file->display_name);
        }

        // Folder → stream as ZIP
        $zipTmp = tempnam(sys_get_temp_dir(), 'erp_folder_') . '.zip';
        $zip    = new \ZipArchive();

        if ($zip->open($zipTmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Could not create ZIP archive.');
        }

        $this->_addFolderToZip($zip, $project, $file, $file->display_name);
        $zip->close();

        return response()
            ->download($zipTmp, $file->display_name . '.zip')
            ->deleteFileAfterSend(true);
    }

    private function _addFolderToZip(\ZipArchive $zip, Project $project, ProjectFile $folder, string $zipPath): void
    {
        // Add an empty directory entry so empty folders appear in the ZIP
        $zip->addEmptyDir($zipPath);

        foreach (ProjectFile::where('parent_id', $folder->id)->get() as $child) {
            if ($child->is_folder) {
                $this->_addFolderToZip($zip, $project, $child, $zipPath . '/' . $child->display_name);
            } else {
                $absPath = Storage::disk('public')->path("project_files/{$project->id}/{$child->stored_name}");
                if (file_exists($absPath)) {
                    $zip->addFile($absPath, $zipPath . '/' . $child->display_name);
                }
            }
        }
    }


    /**
     * Check if current user is assigned to the Project
     */
    private function _isAssigned(Project $project, $user)
    {
        $teamIds = $user->teams->pluck('id');
        return $project->users()->where('users.id', $user->id)->exists()
            || $project->teams()->whereIn('teams.id', $teamIds)->exists();
    }

    /**
     * Check list of projects to show based on user's permission
     */
    private function _scopeQuery($query, $user)
    {
        if ($user->can('view all projects')) {
            return $query;
        }

        if ($user->can('view assigned projects')) {
            $teamIds = $user->teams->pluck('id');
            return $query->where(function ($q) use ($user, $teamIds) {
                $q->whereHas('users', fn($q) => $q->where('users.id', $user->id))
                  ->orWhereHas('teams', fn($q) => $q->whereIn('teams.id', $teamIds));
            });
        }

        abort(403);
    }

    /**
     * Check if current user is assigned to the Project
     */
    private function _canAccessProject(Project $project, $user): bool
    {
        return $user->can('view all projects') ||
               ($user->can('view assigned projects') && $this->_isAssigned($project, $user));
    }

}
