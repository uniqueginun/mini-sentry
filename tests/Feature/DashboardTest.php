<?php

use App\Enums\TeamRole;
use App\Models\Event;
use App\Models\Issue;
use App\Models\IssueStat;
use App\Models\Project;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('has_projects', false)
            ->where('stats.unresolved_issues', 0)
            ->where('stats.events_last_24h', 0)
            ->where('stats.affected_users', 0)
            ->has('sparkline', 24)
            ->where('sparkline.0', 0)
            ->has('issues', 0)
            ->has('pendingInvitations', 0),
        );
});

test('the dashboard includes unresolved issues and stats for the current team', function () {
    $this->travelTo('2026-09-04 15:00:00');

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
    Event::factory()->recycle($issue)->create([
        'occurred_at' => now()->subHours(25),
    ]);
    IssueStat::factory()->recycle($issue)->create([
        'bucket' => now()->startOfHour(),
        'count' => 5,
    ]);
    IssueStat::factory()->recycle($issue)->create([
        'bucket' => now()->subHour()->startOfHour(),
        'count' => 3,
    ]);
    Issue::factory()->recycle($project)->resolved()->create([
        'title' => 'Resolved issue',
        'user_count' => 9,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('has_projects', true)
            ->where('stats.unresolved_issues', 1)
            ->where('stats.events_last_24h', 1)
            ->where('stats.affected_users', 3)
            ->has('sparkline', 24)
            ->where('sparkline.0', 0)
            ->where('sparkline.22', 3)
            ->where('sparkline.23', 5)
            ->has('issues', 1, fn (Assert $item) => $item
                ->where('id', $issue->id)
                ->where('title', 'TypeError: foo()')
                ->where('project.id', $project->id)
                ->where('project.name', 'Checkout')
                ->where('environment', 'production')
                ->has('sparkline', 24)
                ->missing('payload')
                ->etc())
            ->has('pendingInvitations', 0),
        );
});

test('the dashboard does not include issues or events from another team', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->recycle($user)->recycle($user->currentTeam)->create();
    $otherProject = Project::factory()->recycle($otherUser)->recycle($otherUser->currentTeam)->create();
    Issue::factory()->recycle($project)->create([
        'title' => 'Mine',
        'user_count' => 2,
    ]);
    $theirs = Issue::factory()->recycle($otherProject)->create([
        'title' => 'Theirs',
        'user_count' => 50,
    ]);
    Event::factory()->recycle($theirs)->create([
        'occurred_at' => now(),
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard', ['current_team' => $user->currentTeam]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.unresolved_issues', 1)
            ->where('stats.events_last_24h', 0)
            ->where('stats.affected_users', 2)
            ->has('issues', 1, fn (Assert $item) => $item
                ->where('title', 'Mine')
                ->etc())
            ->etc(),
        );
});

test('the dashboard includes regressed issues in the unresolved feed', function () {
    $user = User::factory()->create();
    $project = Project::factory()->recycle($user)->recycle($user->currentTeam)->create();
    Issue::factory()->recycle($project)->regressed()->create([
        'title' => 'Came back',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.unresolved_issues', 1)
            ->has('issues', 1, fn (Assert $item) => $item
                ->where('title', 'Came back')
                ->where('status', 'regressed')
                ->etc())
            ->etc(),
        );
});

test('the dashboard feed includes at most eight unresolved issues', function () {
    $user = User::factory()->create();
    $project = Project::factory()->recycle($user)->recycle($user->currentTeam)->create();
    Issue::factory()->recycle($project)->count(9)->create();

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.unresolved_issues', 9)
            ->has('issues', 8)
            ->etc(),
        );
});

test('dashboard includes pending invitations for the authenticated user', function () {
    $owner = User::factory()->create(['name' => 'Taylor Otwell']);
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create(['name' => 'Laravel Team']);

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('pendingInvitations', 1)
        ->where('pendingInvitations.0.code', $invitation->code)
        ->where('pendingInvitations.0.inviterName', 'Taylor Otwell')
        ->where('pendingInvitations.0.team.name', 'Laravel Team')
        ->where('pendingInvitations.0.team.slug', $team->slug)
        ->missing('pendingInvitations.0.teamName')
        ->etc(),
    );
});

test('dashboard does not include accepted invitations', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    TeamInvitation::factory()->accepted()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('pendingInvitations', 0)
        ->etc(),
    );
});

test('dashboard excludes expired invitations without deleting them', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->expired()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('pendingInvitations', 0)
        ->etc(),
    );

    $this->assertDatabaseHas('team_invitations', [
        'id' => $invitation->id,
    ]);
});

test('dashboard does not include or delete other users invitations', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->expired()->create([
        'team_id' => $team->id,
        'email' => 'someone@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('pendingInvitations', 0)
        ->etc(),
    );

    $this->assertDatabaseHas('team_invitations', [
        'id' => $invitation->id,
    ]);
});
