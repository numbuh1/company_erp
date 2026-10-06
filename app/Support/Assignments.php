<?php

namespace App\Support;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class Assignments
{
    /**
     * Projects the user belongs to (directly or via a team) and tasks assigned to them,
     * as offered in the OT and WFH request forms.
     */
    public static function projectsAndTasksFor(User $user): array
    {
        $userId = $user->id;

        $projects = Project::where(function ($q) use ($userId) {
            $q->whereHas('users', fn ($q2) => $q2->where('users.id', $userId))
              ->orWhereHas('teams', fn ($q2) => $q2->whereHas('users', fn ($q3) => $q3->where('users.id', $userId)));
        })->orderBy('name')->get(['id', 'name', 'project_code']);

        $tasks = Task::whereHas('assignees', fn ($q) => $q->where('users.id', $userId))
            ->orderBy('name')
            ->get(['id', 'name', 'project_id', 'task_code']);

        return compact('projects', 'tasks');
    }
}
