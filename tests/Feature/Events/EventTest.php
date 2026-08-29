<?php

use App\Models\Event;
use App\Models\Issue;
use App\Models\Project;
use Illuminate\Database\UniqueConstraintViolationException;

test('an event uses a uuid primary key and belongs to its issue project', function () {
    $issue = Issue::factory()->create();

    $event = Event::factory()->recycle($issue)->create([
        'event_id' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
        'payload' => ['request' => ['url' => '/checkout']],
    ]);

    expect($event->id)->toBeUuid();
    expect($event->project_id)->toBe($issue->project_id);
    expect($event->issue->is($issue))->toBeTrue();
    expect($event->fresh()->payload)->toBe(['request' => ['url' => '/checkout']]);
});

test('events cannot share an event id within the same project', function () {
    $project = Project::factory()->create();
    Event::factory()->recycle($project)->create(['event_id' => 'duplicate-event']);

    Event::factory()->recycle($project)->create(['event_id' => 'duplicate-event']);
})->throws(UniqueConstraintViolationException::class);

test('events may share an event id across projects', function () {
    Event::factory()->create(['event_id' => 'shared-event']);
    Event::factory()->create(['event_id' => 'shared-event']);

    expect(Event::query()->where('event_id', 'shared-event')->count())->toBe(2);
});
