<?php

use App\Models\Project;
use App\Models\ProjectKey;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('users can generate keys for a project on their team', function () {
    $user = User::factory()->create();
    $project = Project::factory()->recycle($user)->recycle($user->currentTeam)->create();

    expect(Gate::forUser($user)->allows('create', [ProjectKey::class, $project]))->toBeTrue();
});

test('users cannot generate keys for a project on another team', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->recycle($otherUser)->recycle($otherUser->currentTeam)->create();

    expect(Gate::forUser($user)->denies('create', [ProjectKey::class, $project]))->toBeTrue();
});

test('users can revoke keys for a project on their team', function () {
    $user = User::factory()->create();
    $project = Project::factory()->recycle($user)->recycle($user->currentTeam)->create();
    $key = ProjectKey::factory()->recycle($project)->create();

    expect(Gate::forUser($user)->allows('update', $key))->toBeTrue();
});

test('users cannot revoke keys for a project on another team', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->recycle($otherUser)->recycle($otherUser->currentTeam)->create();
    $key = ProjectKey::factory()->recycle($project)->create();

    expect(Gate::forUser($user)->denies('update', $key))->toBeTrue();
});
