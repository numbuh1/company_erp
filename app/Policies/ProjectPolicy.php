<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function update(User $user, Project $project): bool
    {
        return $user->can('edit projects')
            || ($user->can('edit assigned projects') && in_array($project->id, $user->assignedProjectIds(), true));
    }
}
