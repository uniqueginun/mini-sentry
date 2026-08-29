<?php

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('users with a current team can view any projects', function () {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->allows('viewAny', Project::class))->toBeTrue();
});

test('users can view a project that belongs to their team', function () {
    $user = User::factory()->create();
    $project = Project::factory()->recycle($user)->recycle($user->currentTeam)->create();

    expect(Gate::forUser($user)->allows('view', $project))->toBeTrue();
});

test('users cannot view a project that belongs to another team', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->recycle($otherUser)->recycle($otherUser->currentTeam)->create();

    expect(Gate::forUser($user)->denies('view', $project))->toBeTrue();
});

test('users can create a project on a team they belong to', function () {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->allows('create', [Project::class, $user->currentTeam]))->toBeTrue();
});

test('users cannot create a project on a team they do not belong to', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    expect(Gate::forUser($user)->denies('create', [Project::class, $team]))->toBeTrue();
});
