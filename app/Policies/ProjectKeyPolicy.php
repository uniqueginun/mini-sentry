<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\ProjectKey;
use App\Models\User;

class ProjectKeyPolicy
{
    /**
     * Determine whether the user can create keys for the project.
     */
    public function create(User $user, Project $project): bool
    {
        return $user->can('view', $project);
    }

    /**
     * Determine whether the user can deactivate the key.
     */
    public function update(User $user, ProjectKey $projectKey): bool
    {
        return $user->can('view', $projectKey->project);
    }
}
