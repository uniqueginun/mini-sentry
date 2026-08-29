<?php

use App\Models\Event;
use App\Models\Issue;
use App\Models\IssueStat;
use App\Models\Project;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $user = User::factory()->create();
    $project = Project::factory()->recycle($user)->recycle($user->currentTeam)->create();

    $response = $this->get(route('projects.issues.index', [
        'current_team' => $user->currentTeam,
        'project' => $project,
    ]));

    $response->assertRedirect(route('login'));
});

test('the issues page lists unresolved issues from the project', function () {
    $this->freezeTime();

    $user = User::factory()->create();
    $project = Project::factory()->recycle($user)->recycle($user->currentTeam)->create(['name' => 'Checkout']);
    $issue = Issue::factory()->recycle($project)->create([
        'title' => 'TypeError: foo()',
        'culprit' => 'CheckoutController.php',
        'event_count' => 12,
        'user_count' => 3,
        'last_seen' => now(),
    ]);
    Event::factory()->recycle($issue)->create([
        'environment' => 'production',
        'occurred_at' => now(),
        'payload' => ['event' => ['level' => 'error']],
    ]);
    Issue::factory()->recycle($project)->resolved()->create([
        'title' => 'Resolved issue',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('projects.issues.index', [
            'current_team' => $user->currentTeam,
            'project' => $project,
        ]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('projects/issues/index')
            ->where('project.id', $project->id)
            ->where('project.name', 'Checkout')
            ->where('filters.status', 'unresolved')
            ->where('filters.sort', 'last_seen')
            ->has('issues.data', 1)
            ->where('issues.data.0.id', $issue->id)
            ->where('issues.data.0.title', 'TypeError: foo()')
            ->where('issues.data.0.culprit', 'CheckoutController.php')
            ->where('issues.data.0.status', 'unresolved')
            ->where('issues.data.0.event_count', 12)
            ->where('issues.data.0.user_count', 3)
            ->where('issues.data.0.environment', 'production')
            ->where('issues.data.0.level', 'error')
            ->has('issues.data.0.sparkline', 24)
            ->missing('issues.data.0.payload')
            ->where('issues.meta.total', 1)
            ->has('environments', 1)
            ->where('environments.0', 'production'),
        );
});

test('the issues page does not include issues from another project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->recycle($user)->recycle($user->currentTeam)->create();
    $otherProject = Project::factory()->recycle($user)->recycle($user->currentTeam)->create();
    Issue::factory()->recycle($project)->create(['title' => 'Mine']);
    Issue::factory()->recycle($otherProject)->create(['title' => 'Theirs']);

    $response = $this
        ->actingAs($user)
        ->get(route('projects.issues.index', [
            'current_team' => $user->currentTeam,
            'project' => $project,
        ]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('projects/issues/index')
            ->has('issues.data', 1)
            ->where('issues.data.0.title', 'Mine'),
        );
});

test('users who do not belong to the team cannot view its issues', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->recycle($otherUser)->recycle($otherUser->currentTeam)->create();

    $response = $this
        ->actingAs($user)
        ->get(route('projects.issues.index', [
            'current_team' => $user->currentTeam,
            'project' => $project,
        ]));

    $response->assertNotFound();
});

test('resolved issues appear when the status filter is all', function () {
    $user = User::factory()->create();
    $project = Project::factory()->recycle($user)->recycle($user->currentTeam)->create();
    Issue::factory()->recycle($project)->create(['title' => 'Open']);
    Issue::factory()->recycle($project)->resolved()->create(['title' => 'Closed']);

    $response = $this
        ->actingAs($user)
        ->get(route('projects.issues.index', [
            'current_team' => $user->currentTeam,
            'project' => $project,
            'status' => 'all',
        ]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('issues.data', 2)
            ->where('filters.status', 'all'),
        );
});

test('issues can be searched by title', function () {
    $user = User::factory()->create();
    $project = Project::factory()->recycle($user)->recycle($user->currentTeam)->create();
    Issue::factory()->recycle($project)->create(['title' => 'DivisionByZero']);
    Issue::factory()->recycle($project)->create(['title' => 'Timeout']);

    $response = $this
        ->actingAs($user)
        ->get(route('projects.issues.index', [
            'current_team' => $user->currentTeam,
            'project' => $project,
            'search' => 'Division',
        ]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('issues.data', 1)
            ->where('issues.data.0.title', 'DivisionByZero')
            ->where('filters.search', 'Division'),
        );
});

test('a percent sign in search does not match every issue', function () {
    $user = User::factory()->create();
    $project = Project::factory()->recycle($user)->recycle($user->currentTeam)->create();
    Issue::factory()->recycle($project)->create(['title' => 'Timeout']);

    $response = $this
        ->actingAs($user)
        ->get(route('projects.issues.index', [
            'current_team' => $user->currentTeam,
            'project' => $project,
            'search' => '%',
        ]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('issues.data', 0),
        );
});

test('issues can be filtered by environment', function () {
    $user = User::factory()->create();
    $project = Project::factory()->recycle($user)->recycle($user->currentTeam)->create();
    $production = Issue::factory()->recycle($project)->create(['title' => 'Prod crash']);
    $staging = Issue::factory()->recycle($project)->create(['title' => 'Staging crash']);
    Event::factory()->recycle($production)->create(['environment' => 'production']);
    Event::factory()->recycle($staging)->create(['environment' => 'staging']);

    $response = $this
        ->actingAs($user)
        ->get(route('projects.issues.index', [
            'current_team' => $user->currentTeam,
            'project' => $project,
            'environment' => 'production',
        ]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('issues.data', 1)
            ->where('issues.data.0.title', 'Prod crash')
            ->where('filters.environment', 'production'),
        );
});

test('issues can be sorted by event count', function () {
    $user = User::factory()->create();
    $project = Project::factory()->recycle($user)->recycle($user->currentTeam)->create();
    Issue::factory()->recycle($project)->create([
        'title' => 'Rare',
        'event_count' => 2,
        'last_seen' => now(),
    ]);
    Issue::factory()->recycle($project)->create([
        'title' => 'Frequent',
        'event_count' => 40,
        'last_seen' => now()->subHour(),
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('projects.issues.index', [
            'current_team' => $user->currentTeam,
            'project' => $project,
            'sort' => 'events',
        ]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('issues.data.0.title', 'Frequent')
            ->where('issues.data.1.title', 'Rare')
            ->where('filters.sort', 'events'),
        );
});

test('the issue sparkline uses hourly buckets from issue stats', function () {
    $this->travelTo('2026-08-29 15:00:00');

    $user = User::factory()->create();
    $project = Project::factory()->recycle($user)->recycle($user->currentTeam)->create();
    $issue = Issue::factory()->recycle($project)->create();
    IssueStat::factory()->recycle($issue)->create([
        'bucket' => now()->startOfHour(),
        'count' => 5,
    ]);
    IssueStat::factory()->recycle($issue)->create([
        'bucket' => now()->subHours(3)->startOfHour(),
        'count' => 2,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('projects.issues.index', [
            'current_team' => $user->currentTeam,
            'project' => $project,
        ]));

    $sparkline = $response->inertiaProps('issues.data.0.sparkline');

    expect($sparkline)->toHaveCount(24)
        ->and($sparkline[23])->toBe(5)
        ->and($sparkline[20])->toBe(2)
        ->and(array_sum($sparkline))->toBe(7);
});

test('invalid issue filters are rejected', function (string $field, mixed $value, string $message) {
    $user = User::factory()->create();
    $project = Project::factory()->recycle($user)->recycle($user->currentTeam)->create();

    $response = $this
        ->actingAs($user)
        ->from(route('projects.issues.index', [
            'current_team' => $user->currentTeam,
            'project' => $project,
        ]))
        ->get(route('projects.issues.index', [
            'current_team' => $user->currentTeam,
            'project' => $project,
            $field => $value,
        ]));

    $response->assertRedirect(route('projects.issues.index', [
        'current_team' => $user->currentTeam,
        'project' => $project,
    ]));
    $response->assertSessionHasErrors([$field => $message]);
})->with([
    'unknown sort' => ['sort', 'injected', 'The selected sort is invalid.'],
    'unknown status' => ['status', 'open', 'The selected status is invalid.'],
    'search too long' => ['search', str_repeat('a', 256), 'The search field must not be greater than 255 characters.'],
]);
