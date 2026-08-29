<?php

use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\Project;
use App\Services\IssueService;

it('creates an unresolved issue for a new fingerprint', function () {
    $this->travelTo('2026-08-27 00:00:00');
    $project = Project::factory()->create();

    $issue = (new IssueService)->findOrCreateIssue($project, 'abc123', [
        'event' => [
            'culprit' => 'InvoiceService::calculateTotal',
            'exception' => [
                'values' => [[
                    'type' => 'DivisionByZeroError',
                    'message' => 'Division by zero',
                ]],
            ],
        ],
    ]);

    expect($issue->wasRecentlyCreated)->toBeTrue();
    expect($issue->project_id)->toBe($project->id);
    expect($issue->fingerprint)->toBe('abc123');
    expect($issue->title)->toBe('DivisionByZeroError: Division by zero');
    expect($issue->culprit)->toBe('InvoiceService::calculateTotal');
    expect($issue->status)->toBe(IssueStatus::Unresolved);
    expect($issue->event_count)->toBe(0);
    expect($issue->user_count)->toBe(0);
    expect($issue->first_seen->toDateTimeString())->toBe('2026-08-27 00:00:00');
    expect($issue->last_seen->toDateTimeString())->toBe('2026-08-27 00:00:00');
    expect($project->issues()->count())->toBe(1);
    $this->assertModelExists($issue);
});

it('returns the existing issue for a fingerprint instead of inserting a duplicate', function () {
    $project = Project::factory()->create();
    $existing = Issue::factory()->recycle($project)->create([
        'fingerprint' => 'abc123',
        'title' => 'Original title',
        'culprit' => 'OriginalCulprit::method',
    ]);

    $issue = (new IssueService)->findOrCreateIssue($project, 'abc123', [
        'event' => [
            'culprit' => 'InvoiceService::calculateTotal',
            'exception' => [
                'values' => [[
                    'type' => 'DivisionByZeroError',
                    'message' => 'Division by zero',
                ]],
            ],
        ],
    ]);

    expect($issue->is($existing))->toBeTrue();
    expect($issue->wasRecentlyCreated)->toBeFalse();
    expect($issue->title)->toBe('Original title');
    expect($issue->culprit)->toBe('OriginalCulprit::method');
    expect($project->issues()->count())->toBe(1);
});

it('allows the same fingerprint on different projects', function () {
    $firstProject = Project::factory()->create();
    $secondProject = Project::factory()->create();
    $payload = [
        'event' => [
            'exception' => [
                'values' => [['type' => 'DivisionByZeroError']],
            ],
        ],
    ];

    $first = (new IssueService)->findOrCreateIssue($firstProject, 'abc123', $payload);
    $second = (new IssueService)->findOrCreateIssue($secondProject, 'abc123', $payload);

    expect($second->is($first))->toBeFalse();
    expect($first->fingerprint)->toBe('abc123');
    expect($second->fingerprint)->toBe('abc123');
    expect(Issue::query()->where('fingerprint', 'abc123')->count())->toBe(2);
});

it('uses the exception type as the title when the message is missing', function () {
    $project = Project::factory()->create();

    $issue = (new IssueService)->findOrCreateIssue($project, 'abc123', [
        'event' => [
            'exception' => [
                'values' => [['type' => 'DivisionByZeroError']],
            ],
        ],
    ]);

    expect($issue->title)->toBe('DivisionByZeroError');
});

it('uses Error as the title when the payload has no exception type', function () {
    $project = Project::factory()->create();

    $issue = (new IssueService)->findOrCreateIssue($project, 'abc123', [
        'event' => [],
    ]);

    expect($issue->title)->toBe('Error');
    expect($issue->culprit)->toBeNull();
});

it('truncates titles that would exceed 255 characters', function () {
    $project = Project::factory()->create();

    $issue = (new IssueService)->findOrCreateIssue($project, 'abc123', [
        'event' => [
            'exception' => [
                'values' => [[
                    'type' => 'RuntimeException',
                    'message' => str_repeat('a', 300),
                ]],
            ],
        ],
    ]);

    expect($issue->title)->toBe('RuntimeException: '.str_repeat('a', 237));
    expect(strlen($issue->title))->toBe(255);
});
