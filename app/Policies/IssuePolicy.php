<?php

namespace App\Policies;

use App\Models\Issue;
use App\Models\User;

class IssuePolicy
{
    /**
     * Determine whether the user can view the issue.
     */
    public function view(User $user, Issue $issue): bool
    {
        return $user->can('view', $issue->project);
    }

    /**
     * Determine whether the user can resolve the issue.
     */
    public function resolve(User $user, Issue $issue): bool
    {
        return $this->view($user, $issue);
    }
}
