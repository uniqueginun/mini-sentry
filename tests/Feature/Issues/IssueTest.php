<?php

use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\Project;
use Illuminate\Database\UniqueConstraintViolationException;

test('an issue belongs to a project', function () {
    $project = Project::factory()->create();

    $issue = Issue::factory()->recycle($project)->create([
        'fingerprint' => 'type-error',
        'title' => 'TypeError: foo()',
    ]);

    expect($issue->project->is($project))->toBeTrue();
    $this->assertModelExists($issue);
});

test('a new issue defaults to unresolved with zeroed counts', function () {
    $issue = new Issue([
        'project_id' => Project::factory()->create()->id,
        'fingerprint' => 'type-error',
        'title' => 'TypeError: foo()',
        'first_seen' => now(),
        'last_seen' => now(),
    ]);

    expect($issue->status)->toBe(IssueStatus::Unresolved);
    expect($issue->event_count)->toBe(0);
    expect($issue->user_count)->toBe(0);
});

test('issue status is cast to the issue status enum', function () {
    $issue = Issue::factory()->resolved()->create();

    expect($issue->status)->toBe(IssueStatus::Resolved);
    expect($issue->fresh()->status)->toBe(IssueStatus::Resolved);
});

test('issues cannot share a fingerprint within the same project', function () {
    $project = Project::factory()->create();
    Issue::factory()->recycle($project)->create(['fingerprint' => 'type-error']);

    Issue::factory()->recycle($project)->create(['fingerprint' => 'type-error']);
})->throws(UniqueConstraintViolationException::class);

test('issues may share a fingerprint across projects', function () {
    Issue::factory()->create(['fingerprint' => 'type-error']);
    Issue::factory()->create(['fingerprint' => 'type-error']);

    expect(Issue::query()->where('fingerprint', 'type-error')->count())->toBe(2);
});
