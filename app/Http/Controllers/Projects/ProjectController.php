<?php

namespace App\Http\Controllers\Projects;

use App\Actions\Projects\CreateProject;
use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\StoreProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Display the team's projects and their keys.
     */
    public function index(Team $current_team): Response
    {
        Gate::authorize('viewAny', Project::class);

        $projects = $current_team->projects()
            ->with(['projectKeys' => fn ($query) => $query->latest()])
            ->latest()
            ->get();

        return Inertia::render('projects/index', [
            'projects' => ProjectResource::collection($projects)->resolve(),
        ]);
    }

    /**
     * Store a newly created project and generate its first key.
     */
    public function store(StoreProjectRequest $request, Team $current_team, CreateProject $createProject): RedirectResponse
    {
        $project = $createProject->handle(
            $request->user(),
            $current_team,
            $request->validated('name'),
        );

        $key = $project->projectKeys->firstOrFail();

        Inertia::flash([
            'toast' => ['type' => 'success', 'message' => __('Project created.')],
            'generatedKey' => [
                'projectId' => $project->id,
                'publicKey' => $key->plaintextOrFail(),
            ],
        ]);

        return to_route('projects.index');
    }
}
