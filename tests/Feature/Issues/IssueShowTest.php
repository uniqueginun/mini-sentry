<?php

use App\Enums\IssueStatus;
use App\Models\Event;
use App\Models\EventTag;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $user = User::factory()->create();
    $project = Project::factory()->recycle($user)->recycle($user->currentTeam)->create();
    $issue = Issue::factory()->recycle($project)->create();

    $response = $this->get(route('projects.issues.show', [
        'current_team' => $user->currentTeam,
        'project' => $project,
        'issue' => $issue,
    ]));

    $response->assertRedirect(route('login'));
});

test('guests cannot resolve an issue', function () {
    $user = User::factory()->create();
    $project = Project::factory()->recycle($user)->recycle($user->currentTeam)->create();
    $issue = Issue::factory()->recycle($project)->create();

    $response = $this->post(route('projects.issues.resolve', [
        'current_team' => $user->currentTeam,
        'project' => $project,
        'issue' => $issue,
    ]));

    $response->assertRedirect(route('login'));
    expect($issue->fresh()->status)->toBe(IssueStatus::Unresolved);
});

test('the issue detail page reads metrics from the issue row', function () {
    $this->freezeTime();

    $user = User::factory()->create();
    $project = Project::factory()->recycle($user)->recycle($user->currentTeam)->create(['name' => 'Checkout']);
    $issue = Issue::factory()->recycle($project)->create([
        'title' => 'TypeError: Cannot read properties of undefined',
        'culprit' => 'app/Http/Controllers/CheckoutController.php',
        'event_count' => 142,
        'user_count' => 18,
        'first_seen' => now()->subDays(3),
        'last_seen' => now()->subMinutes(2),
    ]);
    $event = Event::factory()->recycle($issue)->create([
        'event_id' => 'a1b2c3d4e5f6',
        'environment' => 'production',
        'release' => '1.4.3',
        'occurred_at' => now()->subMinutes(2),
        'payload' => [
            'event' => [
                'level' => 'error',
                'exception' => [
                    'values' => [[
                        'stacktrace' => [
                            [
                                'filename' => 'vendor/stripe/lib/ApiRequestor.php',
                                'lineno' => 112,
                                'function' => 'request',
                                'in_app' => false,
                            ],
                            [
                                'filename' => 'app/Http/Controllers/CheckoutController.php',
                                'lineno' => 47,
                                'function' => 'App\\Http\\Controllers\\CheckoutController::pay',
                                'class' => 'App\\Http\\Controllers\\CheckoutController',
                                'in_app' => true,
                            ],
                        ],
                    ]],
                ],
                'breadcrumbs' => [
                    [
                        'category' => 'http',
                        'message' => 'POST /checkout',
                        'level' => 'info',
                    ],
                    [
                        'category' => 'query',
                        'message' => 'SELECT * FROM carts WHERE id = 9',
                        'level' => 'info',
                    ],
                ],
            ],
        ],
    ]);
    EventTag::factory()->recycle($event)->create([
        'key' => 'browser',
        'value' => 'Chrome 128',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('projects.issues.show', [
            'current_team' => $user->currentTeam,
            'project' => $project,
            'issue' => $issue,
        ]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('projects/issues/show')
            ->where('project.name', 'Checkout')
            ->where('issue.id', $issue->id)
            ->where('issue.title', 'TypeError: Cannot read properties of undefined')
            ->where('issue.culprit', 'app/Http/Controllers/CheckoutController.php')
            ->where('issue.status', 'unresolved')
            ->where('issue.event_count', 142)
            ->where('issue.user_count', 18)
            ->where('issue.level', 'error')
            ->has('issue.sparkline', 24)
            ->missing('issue.payload')
            ->where('issue.latest_event.event_id', 'a1b2c3d4e5f6')
            ->where('issue.latest_event.environment', 'production')
            ->where('issue.latest_event.release', '1.4.3')
            ->where('issue.latest_event.stacktrace.0.filename', 'app/Http/Controllers/CheckoutController.php')
            ->where('issue.latest_event.stacktrace.0.lineno', 47)
            ->where('issue.latest_event.stacktrace.0.in_app', true)
            ->where('issue.latest_event.stacktrace.0.is_culprit', true)
            ->where('issue.latest_event.stacktrace.1.is_culprit', false)
            ->where('issue.latest_event.breadcrumbs.0.message', 'POST /checkout')
            ->has('issue.latest_event.tags', 1)
            ->where('issue.latest_event.tags.0.key', 'browser')
            ->where('issue.latest_event.tags.0.value', 'Chrome 128')
            ->missing('issue.latest_event.payload'),
        );
});

test('the stack trace highlights the in-app frame instead of the front controller', function () {
    $user = User::factory()->create();
    $project = Project::factory()->recycle($user)->recycle($user->currentTeam)->create();
    $issue = Issue::factory()->recycle($project)->create();
    Event::factory()->recycle($issue)->create([
        'payload' => [
            'event' => [
                'level' => 'error',
                'exception' => [
                    'values' => [[
                        'stacktrace' => [
                            [
                                'filename' => '/var/www/laravel/upload-hub/public/index.php',
                                'lineno' => 20,
                                'function' => 'handleRequest',
                                'in_app' => true,
                            ],
                            [
                                'filename' => '/var/www/laravel/upload-hub/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php',
                                'lineno' => 144,
                                'function' => 'sendRequestThroughRouter',
                                'in_app' => false,
                            ],
                            [
                                'filename' => '/var/www/laravel/upload-hub/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php',
                                'lineno' => 219,
                                'function' => 'handle',
                                'in_app' => false,
                            ],
                            [
                                'filename' => '/var/www/laravel/upload-hub/vendor/laravel/framework/src/Illuminate/Routing/Router.php',
                                'lineno' => 822,
                                'function' => 'run',
                                'in_app' => false,
                            ],
                            [
                                'filename' => '/var/www/laravel/upload-hub/app/MiniSentry/ExceptionSampleGenerator.php',
                                'lineno' => 126,
                                'function' => 'networkReal',
                                'in_app' => true,
                            ],
                            [
                                'filename' => '/var/www/laravel/upload-hub/vendor/guzzlehttp/guzzle/src/Handler/CurlFactory.php',
                                'lineno' => 200,
                                'function' => 'createRejection',
                                'in_app' => false,
                            ],
                        ],
                    ]],
                ],
            ],
        ],
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('projects.issues.show', [
            'current_team' => $user->currentTeam,
            'project' => $project,
            'issue' => $issue,
        ]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('issue.latest_event.stacktrace.0.filename', '/var/www/laravel/upload-hub/vendor/guzzlehttp/guzzle/src/Handler/CurlFactory.php')
            ->where('issue.latest_event.stacktrace.0.is_culprit', false)
            ->where('issue.latest_event.stacktrace.1.filename', '/var/www/laravel/upload-hub/app/MiniSentry/ExceptionSampleGenerator.php')
            ->where('issue.latest_event.stacktrace.1.lineno', 126)
            ->where('issue.latest_event.stacktrace.1.function', 'networkReal')
            ->where('issue.latest_event.stacktrace.1.is_culprit', true)
            ->where('issue.latest_event.stacktrace.5.filename', '/var/www/laravel/upload-hub/public/index.php')
            ->where('issue.latest_event.stacktrace.5.is_culprit', false),
        );
});

test('the issue detail page shows a regressed status', function () {
    $user = User::factory()->create();
    $project = Project::factory()->recycle($user)->recycle($user->currentTeam)->create();
    $issue = Issue::factory()->recycle($project)->regressed()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('projects.issues.show', [
            'current_team' => $user->currentTeam,
            'project' => $project,
            'issue' => $issue,
        ]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('issue.status', 'regressed'),
        );
});

test('users who do not belong to the team cannot view the issue', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->recycle($otherUser)->recycle($otherUser->currentTeam)->create();
    $issue = Issue::factory()->recycle($project)->create();

    $response = $this
        ->actingAs($user)
        ->get(route('projects.issues.show', [
            'current_team' => $user->currentTeam,
            'project' => $project,
            'issue' => $issue,
        ]));

    $response->assertNotFound();
});

test('an issue from another project is not found', function () {
    $user = User::factory()->create();
    $project = Project::factory()->recycle($user)->recycle($user->currentTeam)->create();
    $otherProject = Project::factory()->recycle($user)->recycle($user->currentTeam)->create();
    $issue = Issue::factory()->recycle($otherProject)->create();

    $response = $this
        ->actingAs($user)
        ->get(route('projects.issues.show', [
            'current_team' => $user->currentTeam,
            'project' => $project,
            'issue' => $issue,
        ]));

    $response->assertNotFound();
});

test('an unresolved issue can be resolved', function () {
    $user = User::factory()->create();
    $project = Project::factory()->recycle($user)->recycle($user->currentTeam)->create();
    $issue = Issue::factory()->recycle($project)->create();

    $response = $this
        ->actingAs($user)
        ->from(route('projects.issues.show', [
            'current_team' => $user->currentTeam,
            'project' => $project,
            'issue' => $issue,
        ]))
        ->post(route('projects.issues.resolve', [
            'current_team' => $user->currentTeam,
            'project' => $project,
            'issue' => $issue,
        ]));

    $response
        ->assertRedirect(route('projects.issues.show', [
            'current_team' => $user->currentTeam,
            'project' => $project,
            'issue' => $issue,
        ]))
        ->assertInertiaFlash('toast.type', 'success')
        ->assertInertiaFlash('toast.message', 'Issue resolved.');

    expect($issue->fresh()->status)->toBe(IssueStatus::Resolved);
});

test('users who do not belong to the team cannot resolve the issue', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->recycle($otherUser)->recycle($otherUser->currentTeam)->create();
    $issue = Issue::factory()->recycle($project)->create();

    $response = $this
        ->actingAs($user)
        ->post(route('projects.issues.resolve', [
            'current_team' => $user->currentTeam,
            'project' => $project,
            'issue' => $issue,
        ]));

    $response->assertNotFound();
    expect($issue->fresh()->status)->toBe(IssueStatus::Unresolved);
});
