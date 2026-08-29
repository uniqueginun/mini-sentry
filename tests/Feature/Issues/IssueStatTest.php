<?php

use App\Models\Issue;
use App\Models\IssueStat;
use Illuminate\Database\UniqueConstraintViolationException;

test('an issue stat belongs to an issue', function () {
    $issue = Issue::factory()->create();

    $stat = IssueStat::factory()->recycle($issue)->create([
        'bucket' => now()->startOfHour(),
        'count' => 4,
    ]);

    expect($stat->issue->is($issue))->toBeTrue();
    expect($stat->count)->toBe(4);
});

test('issue stats cannot share a bucket within the same issue', function () {
    $issue = Issue::factory()->create();
    $bucket = now()->startOfHour();
    IssueStat::factory()->recycle($issue)->create(['bucket' => $bucket, 'count' => 1]);

    IssueStat::factory()->recycle($issue)->create(['bucket' => $bucket, 'count' => 2]);
})->throws(UniqueConstraintViolationException::class);

test('issue stats may share a bucket across issues', function () {
    $bucket = now()->startOfHour();
    IssueStat::factory()->create(['bucket' => $bucket]);
    IssueStat::factory()->create(['bucket' => $bucket]);

    expect(IssueStat::query()->where('bucket', $bucket)->count())->toBe(2);
});
