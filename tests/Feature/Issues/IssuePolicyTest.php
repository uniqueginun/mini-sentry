<?php

use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('users can view an issue that belongs to their team', function () {
    $user = User::factory()->create();
    $project = Project::factory()->recycle($user)->recycle($user->currentTeam)->create();
    $issue = Issue::factory()->recycle($project)->create();

    expect(Gate::forUser($user)->allows('view', $issue))->toBeTrue();
});

test('users cannot view an issue that belongs to another team', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->recycle($otherUser)->recycle($otherUser->currentTeam)->create();
    $issue = Issue::factory()->recycle($project)->create();

    expect(Gate::forUser($user)->denies('view', $issue))->toBeTrue();
});

test('users can resolve an issue that belongs to their team', function () {
    $user = User::factory()->create();
    $project = Project::factory()->recycle($user)->recycle($user->currentTeam)->create();
    $issue = Issue::factory()->recycle($project)->create();

    expect(Gate::forUser($user)->allows('resolve', $issue))->toBeTrue();
});

test('users cannot resolve an issue that belongs to another team', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->recycle($otherUser)->recycle($otherUser->currentTeam)->create();
    $issue = Issue::factory()->recycle($project)->create();

    expect(Gate::forUser($user)->denies('resolve', $issue))->toBeTrue();
});
