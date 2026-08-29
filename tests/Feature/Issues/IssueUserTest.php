<?php

use App\Models\Issue;
use App\Models\IssueUser;
use Illuminate\Database\UniqueConstraintViolationException;

test('an issue user belongs to an issue', function () {
    $issue = Issue::factory()->create();

    $issueUser = IssueUser::factory()->recycle($issue)->create([
        'identifier' => 'user-42',
    ]);

    expect($issueUser->issue->is($issue))->toBeTrue();
    expect($issueUser->identifier)->toBe('user-42');
});

test('issue users cannot share an identifier within the same issue', function () {
    $issue = Issue::factory()->create();
    IssueUser::factory()->recycle($issue)->create(['identifier' => 'user-42']);

    IssueUser::factory()->recycle($issue)->create(['identifier' => 'user-42']);
})->throws(UniqueConstraintViolationException::class);

test('issue users may share an identifier across issues', function () {
    IssueUser::factory()->create(['identifier' => 'user-42']);
    IssueUser::factory()->create(['identifier' => 'user-42']);

    expect(IssueUser::query()->where('identifier', 'user-42')->count())->toBe(2);
});
