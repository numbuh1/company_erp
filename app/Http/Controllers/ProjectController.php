<?php
namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Team;
use App\Models\User;
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
        return view('projects.index', compact('projects'));
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
            'status'            => 'nullable|string|in:Not Started,In Progress,Done',
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

        $project->load(['teams.users', 'users', 'tasks.assignees', 'comments.user']);

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

        return view('projects.show', compact('project', 'items', 'currentFolder', 'breadcrumb', 'activities', 'canUpload', 'canManageAll'));
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
            'status'            => 'nullable|string|in:Not Started,In Progress,Done',
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
