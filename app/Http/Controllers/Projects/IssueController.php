<?php

namespace App\Http\Controllers\Projects;

use App\Enums\IssueSort;
use App\Enums\IssueStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Issues\IndexIssueRequest;
use App\Http\Requests\Issues\ResolveIssueRequest;
use App\Http\Resources\IssueDetailResource;
use App\Http\Resources\IssueResource;
use App\Http\Resources\ProjectResource;
use App\Models\Issue;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class IssueController extends Controller
{
    /**
     * Display a listing of the project's issues.
     */
    public function index(IndexIssueRequest $request, Team $current_team, Project $project): Response
    {
        $filters = $request->filters();
        $sort = IssueSort::from($filters['sort']);

        $issues = $project->issues()
            ->with([
                'latestEvent',
                'stats' => fn ($query) => $query
                    ->where('bucket', '>=', Issue::sparklineWindowStart())
                    ->orderBy('bucket'),
            ])
            ->search($filters['search'])
            ->statusFilter($filters['status'])
            ->forEnvironment($filters['environment'])
            ->forRelease($filters['release'])
            ->sortedBy($sort)
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('projects/issues/index', [
            'project' => (new ProjectResource($project))->resolve(),
            'issues' => [
                'data' => IssueResource::collection($issues->getCollection())->resolve(),
                'meta' => [
                    'total' => $issues->total(),
                    'from' => $issues->firstItem(),
                    'to' => $issues->lastItem(),
                    'current_page' => $issues->currentPage(),
                    'last_page' => $issues->lastPage(),
                    'prev_page_url' => $issues->previousPageUrl(),
                    'next_page_url' => $issues->nextPageUrl(),
                ],
            ],
            'filters' => $filters,
            'environments' => $project->events()
                ->whereNotNull('environment')
                ->distinct()
                ->orderBy('environment')
                ->pluck('environment')
                ->all(),
            'releases' => $project->events()
                ->whereNotNull('release')
                ->distinct()
                ->orderBy('release')
                ->pluck('release')
                ->all(),
        ]);
    }

    /**
     * Display the issue.
     */
    public function show(Team $current_team, Project $project, Issue $issue): Response
    {
        Gate::authorize('view', $issue);

        $issue->load([
            'latestEvent.tags',
            'stats' => fn ($query) => $query
                ->where('bucket', '>=', Issue::sparklineWindowStart())
                ->orderBy('bucket'),
        ]);

        return Inertia::render('projects/issues/show', [
            'project' => (new ProjectResource($project))->resolve(),
            'issue' => (new IssueDetailResource($issue))->resolve(),
        ]);
    }

    /**
     * Mark the issue as resolved.
     */
    public function resolve(ResolveIssueRequest $request, Team $current_team, Project $project, Issue $issue): RedirectResponse
    {
        $issue->update([
            'status' => IssueStatus::Resolved,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Issue resolved.')]);

        return back();
    }
}
