<?php

use App\Models\AlertHistory;
use App\Models\AlertRule;
use App\Models\Event;
use App\Models\Issue;
use App\Models\Project;

test('an alert history belongs to a rule issue and event', function () {
    $project = Project::factory()->create();
    $issue = Issue::factory()->recycle($project)->create();
    $event = Event::factory()->recycle($issue)->create();
    $rule = AlertRule::factory()->recycle($project)->create();

    $history = AlertHistory::factory()
        ->recycle($rule)
        ->recycle($issue)
        ->recycle($event)
        ->create();

    expect($history->alertRule->is($rule))->toBeTrue();
    expect($history->issue->is($issue))->toBeTrue();
    expect($history->event->is($event))->toBeTrue();
});
