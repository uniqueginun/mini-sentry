<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\RevokeProjectKeyRequest;
use App\Http\Requests\Projects\StoreProjectKeyRequest;
use App\Models\Project;
use App\Models\ProjectKey;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ProjectKeyController extends Controller
{
    /**
     * Generate a new key for the project.
     */
    public function store(StoreProjectKeyRequest $request, Team $current_team, Project $project): RedirectResponse
    {
        $key = $project->projectKeys()->create();

        Inertia::flash([
            'toast' => ['type' => 'success', 'message' => __('New key generated.')],
            'generatedKey' => [
                'projectId' => $project->id,
                'publicKey' => $key->plaintextOrFail(),
            ],
        ]);

        return to_route('projects.index');
    }

    /**
     * Deactivate the project key.
     */
    public function update(RevokeProjectKeyRequest $request, Team $current_team, Project $project, ProjectKey $projectKey): RedirectResponse
    {
        $projectKey->revoke();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Key revoked.')]);

        return to_route('projects.index');
    }
}
