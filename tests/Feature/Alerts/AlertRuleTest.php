<?php

use App\Enums\AlertRuleType;
use App\Models\AlertRule;
use App\Models\Project;

test('an alert rule belongs to a project and defaults to enabled', function () {
    $project = Project::factory()->create();

    $rule = AlertRule::factory()->recycle($project)->create([
        'name' => 'New issue',
    ]);

    expect($rule->project->is($project))->toBeTrue();
    expect($rule->type)->toBe(AlertRuleType::NewIssue);
    expect($rule->enabled)->toBeTrue();
    expect($rule->cooldown_minutes)->toBe(15);
});

test('an alert rule can target a specific environment', function () {
    $rule = AlertRule::factory()->environment('production')->create();

    expect($rule->type)->toBe(AlertRuleType::Environment);
    expect($rule->environment)->toBe('production');
});
