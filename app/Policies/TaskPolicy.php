<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    private const LIST_PERMISSIONS = [
        'view all tasks', 'view team tasks', 'view assigned tasks',
        'edit tasks', 'edit team tasks', 'edit assigned tasks', 'edit assigned projects',
    ];

    public function viewAny(User $user): bool
    {
        return $user->canAny(self::LIST_PERMISSIONS);
    }

    public function view(User $user, Task $task): bool
    {
        if ($user->can('view all tasks')) return true;

        $assigneeIds = $this->assigneeIds($task);
        if (in_array($user->id, $assigneeIds, true)) return true;
        if ($user->can('view team tasks') && array_intersect($assigneeIds, $user->teamMemberIds())) return true;

        return $this->update($user, $task);
    }

    /** Pass a project to ask "can the user add a task to this project?". */
    public function create(User $user, ?Project $project = null): bool
    {
        if ($user->can('edit tasks') || $user->can('edit team tasks')) return true;
        if (!$user->can('edit assigned projects')) return false;

        return $project
            ? in_array($project->id, $user->assignedProjectIds(), true)
            : !empty($user->assignedProjectIds());
    }

    public function update(User $user, Task $task): bool
    {
        return $this->canManage($user, $task->project_id, $this->assigneeIds($task));
    }

    /**
     * Whether a task with this project and these assignees is inside the user's editing scope.
     * $viaOwnAssignment=false excludes "edit assigned tasks", which never grants creating.
     */
    public function canManage(User $user, ?int $projectId, array $assigneeIds, bool $viaOwnAssignment = true): bool
    {
        $assigneeIds = array_map('intval', $assigneeIds);

        if ($user->can('edit tasks')) return true;
        if ($user->can('edit team tasks') && array_intersect($assigneeIds, $user->teamMemberIds())) return true;
        if ($viaOwnAssignment && $user->can('edit assigned tasks') && in_array($user->id, $assigneeIds, true)) return true;

        return $projectId
            && $user->can('edit assigned projects')
            && in_array($projectId, $user->assignedProjectIds(), true);
    }

    private function assigneeIds(Task $task): array
    {
        $ids = $task->relationLoaded('assignees')
            ? $task->assignees->pluck('id')
            : $task->assignees()->pluck('users.id');

        return $ids->map(fn ($id) => (int) $id)->all();
    }
}
