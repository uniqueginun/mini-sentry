<?php

use App\Enums\IssueStatus;
use App\Jobs\SendIssueAlertJob;
use App\Models\AlertHistory;
use App\Models\AlertRule;
use App\Models\Event;
use App\Models\Issue;
use App\Models\IssueUser;
use App\Models\Project;
use App\Services\ErrorEventProcessor;
use App\Services\FingerPrintCalculator;
use App\Services\IssueService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

function processIncomingEvent(Project $project, array $payload): ?Event
{
    $issue = (new IssueService)->findOrCreateIssue(
        $project,
        (new FingerPrintCalculator)->calculate($project->id, $payload),
        $payload,
    );

    return (new ErrorEventProcessor)->process($issue, $payload);
}

it('stores a unique event and updates issue stats', function () {
    $this->travelTo('2026-08-27 12:34:56');
    $project = Project::factory()->create();
    $payload = errorEventPayload([
        'event_id' => 'evt-1',
        'environment' => 'production',
        'release' => '1.4.3',
        'user' => ['id' => 'user-42'],
        'tags' => ['browser' => 'Chrome', 'url' => '/checkout'],
        'culprit' => 'CheckoutController::pay',
        'fingerprint' => ['payment-processing'],
    ]);

    $event = processIncomingEvent($project, $payload);
    $issue = $project->issues()->first();

    expect($event)->not->toBeNull();
    expect($event->issue_id)->toBe($issue->id);
    expect($event->project_id)->toBe($project->id);
    expect($event->event_id)->toBe('evt-1');
    expect($event->exception_type)->toBe('RuntimeException');
    expect($event->message)->toBe('Stripe timeout');
    expect($event->environment)->toBe('production');
    expect($event->release)->toBe('1.4.3');
    expect($event->user_identifier)->toBe('user-42');
    expect($event->occurred_at->toDateTimeString())->toBe('2026-08-27 12:34:56');
    expect($event->payload)->toBe($payload);
    expect($event->tags()->pluck('value', 'key')->all())->toBe([
        'browser' => 'Chrome',
        'url' => '/checkout',
    ]);
    expect($issue->event_count)->toBe(1);
    expect($issue->user_count)->toBe(1);
    expect($issue->first_seen->toDateTimeString())->toBe('2026-08-27 12:34:56');
    expect($issue->last_seen->toDateTimeString())->toBe('2026-08-27 12:34:56');
    expect($issue->users()->pluck('identifier')->all())->toBe(['user-42']);
    expect($issue->stats()->first()->count)->toBe(1);
    expect($issue->stats()->first()->bucket->toDateTimeString())->toBe('2026-08-27 12:00:00');
});

it('does not store a duplicate event_id for the same project or update stats', function () {
    $project = Project::factory()->create();
    $payload = errorEventPayload([
        'event_id' => 'duplicate-event',
        'user' => ['id' => 'user-1'],
        'fingerprint' => ['payment-processing'],
    ]);

    processIncomingEvent($project, $payload);
    processIncomingEvent($project, $payload);

    $issue = $project->issues()->first();

    expect(Event::query()->where('event_id', 'duplicate-event')->count())->toBe(1);
    expect($issue->event_count)->toBe(1);
    expect($issue->user_count)->toBe(1);
    expect($issue->stats()->sum('count'))->toBe(1);
});

it('allows the same event_id on a different project', function () {
    $firstProject = Project::factory()->create();
    $secondProject = Project::factory()->create();
    $payload = errorEventPayload(['event_id' => 'shared-event']);

    processIncomingEvent($firstProject, $payload);
    processIncomingEvent($secondProject, $payload);

    expect(Event::query()->where('event_id', 'shared-event')->count())->toBe(2);
});

it('does not store an event that has no event_id', function () {
    $project = Project::factory()->create();
    $payload = errorEventPayload();
    unset($payload['event']['event_id']);

    $event = processIncomingEvent($project, $payload);

    expect($event)->toBeNull();
    expect(Event::query()->count())->toBe(0);
    expect($project->issues()->first()->event_count)->toBe(0);
});

it('updates first_seen only when the event is older and last_seen only when it is newer', function () {
    $this->travelTo('2026-08-27 12:00:00');
    $project = Project::factory()->create();
    $fingerprint = ['payment-processing'];

    processIncomingEvent($project, errorEventPayload([
        'event_id' => 'middle',
        'timestamp' => '2026-08-27 12:00:00',
        'fingerprint' => $fingerprint,
    ]));

    processIncomingEvent($project, errorEventPayload([
        'event_id' => 'older',
        'timestamp' => '2026-08-27 10:00:00',
        'fingerprint' => $fingerprint,
    ]));

    processIncomingEvent($project, errorEventPayload([
        'event_id' => 'newer',
        'timestamp' => '2026-08-27 14:00:00',
        'fingerprint' => $fingerprint,
    ]));

    $issue = $project->issues()->first();

    expect($issue->event_count)->toBe(3);
    expect($issue->first_seen->toDateTimeString())->toBe('2026-08-27 10:00:00');
    expect($issue->last_seen->toDateTimeString())->toBe('2026-08-27 14:00:00');
    expect($issue->stats()->count())->toBe(3);
});

it('does not increment unique user counts when no user identifier exists', function () {
    $project = Project::factory()->create();

    processIncomingEvent($project, errorEventPayload(['fingerprint' => ['payment-processing']]));
    processIncomingEvent($project, errorEventPayload([
        'event_id' => (string) Str::uuid(),
        'fingerprint' => ['payment-processing'],
        'user' => ['email' => ''],
    ]));

    expect($project->issues()->first()->user_count)->toBe(0);
    expect(IssueUser::query()->count())->toBe(0);
});

it('does not increment unique user counts for the same identifier twice', function () {
    $project = Project::factory()->create();
    $fingerprint = ['payment-processing'];

    processIncomingEvent($project, errorEventPayload([
        'event_id' => 'first',
        'user' => ['email' => 'buyer@example.com'],
        'fingerprint' => $fingerprint,
    ]));
    processIncomingEvent($project, errorEventPayload([
        'event_id' => 'second',
        'user' => ['id' => 'buyer@example.com'],
        'fingerprint' => $fingerprint,
    ]));
    processIncomingEvent($project, errorEventPayload([
        'event_id' => 'third',
        'user' => ['id' => 'other-user'],
        'fingerprint' => $fingerprint,
    ]));

    $issue = $project->issues()->first();

    expect($issue->event_count)->toBe(3);
    expect($issue->user_count)->toBe(2);
});

it('marks a resolved issue as regressed once and records when it returned', function () {
    $this->travelTo('2026-08-27 15:00:00');
    $project = Project::factory()->create();
    $issue = Issue::factory()->recycle($project)->resolved()->create([
        'fingerprint' => 'abc123',
        'regressed_at' => null,
    ]);

    (new ErrorEventProcessor)->process($issue->refresh(), errorEventPayload(['event_id' => 'first-return']));
    (new ErrorEventProcessor)->process($issue->fresh(), errorEventPayload(['event_id' => 'second-return']));

    $issue->refresh();

    expect($issue->status)->toBe(IssueStatus::Regressed);
    expect($issue->regressed_at->toDateTimeString())->toBe('2026-08-27 15:00:00');
    expect($issue->event_count)->toBe(3);
});

it('does not mark unresolved issues as regressed', function () {
    $project = Project::factory()->create();

    processIncomingEvent($project, errorEventPayload());

    expect($project->issues()->first()->status)->toBe(IssueStatus::Unresolved);
    expect($project->issues()->first()->regressed_at)->toBeNull();
});

it('does not re-mark an already regressed issue', function () {
    $this->travelTo('2026-08-27 15:00:00');
    $project = Project::factory()->create();
    $issue = Issue::factory()->recycle($project)->regressed()->create([
        'fingerprint' => 'abc123',
        'regressed_at' => '2026-08-20 08:00:00',
    ]);

    (new ErrorEventProcessor)->process($issue->refresh(), errorEventPayload());

    $issue->refresh();

    expect($issue->status)->toBe(IssueStatus::Regressed);
    expect($issue->regressed_at->toDateTimeString())->toBe('2026-08-20 08:00:00');
});

it('dispatches alerts for matching enabled rules and records history', function () {
    Queue::fake([SendIssueAlertJob::class]);
    $project = Project::factory()->create();
    $newIssueRule = AlertRule::factory()->recycle($project)->create();
    AlertRule::factory()->recycle($project)->disabled()->create(['name' => 'Disabled new issue']);

    $event = processIncomingEvent($project, errorEventPayload(['fingerprint' => ['payment-processing']]));
    $issue = $project->issues()->first();

    Queue::assertPushed(SendIssueAlertJob::class, 1);
    Queue::assertPushed(fn (SendIssueAlertJob $job): bool => $job->alertRuleId === $newIssueRule->id
        && $job->issueId === $issue->id
        && $job->eventId === $event->id);
    $this->assertDatabaseHas(AlertHistory::class, [
        'alert_rule_id' => $newIssueRule->id,
        'issue_id' => $issue->id,
        'event_id' => $event->id,
    ]);
});

it('dispatches a regression alert when a resolved issue returns', function () {
    Queue::fake([SendIssueAlertJob::class]);
    $project = Project::factory()->create();
    $issue = Issue::factory()->recycle($project)->resolved()->create(['fingerprint' => 'abc123']);
    $rule = AlertRule::factory()->recycle($project)->regression()->create();

    $event = (new ErrorEventProcessor)->process($issue->refresh(), errorEventPayload());

    Queue::assertPushed(fn (SendIssueAlertJob $job): bool => $job->alertRuleId === $rule->id
        && $job->issueId === $issue->id
        && $job->eventId === $event->id);
});

it('dispatches a frequency alert when the event window reaches the threshold', function () {
    Queue::fake([SendIssueAlertJob::class]);
    $this->travelTo('2026-08-27 12:00:00');
    $project = Project::factory()->create();
    $rule = AlertRule::factory()->recycle($project)->eventFrequency(2, 5)->create();
    $fingerprint = ['payment-processing'];

    processIncomingEvent($project, errorEventPayload([
        'event_id' => 'first',
        'fingerprint' => $fingerprint,
    ]));

    Queue::assertNotPushed(SendIssueAlertJob::class);

    $event = processIncomingEvent($project, errorEventPayload([
        'event_id' => 'second',
        'fingerprint' => $fingerprint,
    ]));

    Queue::assertPushed(fn (SendIssueAlertJob $job): bool => $job->alertRuleId === $rule->id
        && $job->eventId === $event->id);
});

it('dispatches an affected-users alert when unique users reach the threshold', function () {
    Queue::fake([SendIssueAlertJob::class]);
    $project = Project::factory()->create();
    $rule = AlertRule::factory()->recycle($project)->affectedUsers(2)->create();
    $fingerprint = ['payment-processing'];

    processIncomingEvent($project, errorEventPayload([
        'event_id' => 'first',
        'user' => ['id' => 'user-1'],
        'fingerprint' => $fingerprint,
    ]));

    Queue::assertNotPushed(SendIssueAlertJob::class);

    processIncomingEvent($project, errorEventPayload([
        'event_id' => 'second',
        'user' => ['id' => 'user-2'],
        'fingerprint' => $fingerprint,
    ]));

    Queue::assertPushed(fn (SendIssueAlertJob $job): bool => $job->alertRuleId === $rule->id);
});

it('dispatches an environment alert for production events', function () {
    Queue::fake([SendIssueAlertJob::class]);
    $project = Project::factory()->create();
    $rule = AlertRule::factory()->recycle($project)->environment('production')->create();
    AlertRule::factory()->recycle($project)->environment('staging')->create();

    processIncomingEvent($project, errorEventPayload(['environment' => 'production']));

    Queue::assertPushed(SendIssueAlertJob::class, 1);
    Queue::assertPushed(fn (SendIssueAlertJob $job): bool => $job->alertRuleId === $rule->id);
});

it('does not evaluate alerts again for a duplicate event', function () {
    Queue::fake([SendIssueAlertJob::class]);
    $project = Project::factory()->create();
    AlertRule::factory()->recycle($project)->create();
    $payload = errorEventPayload(['event_id' => 'once-only', 'fingerprint' => ['payment-processing']]);

    processIncomingEvent($project, $payload);
    processIncomingEvent($project, $payload);

    Queue::assertPushed(SendIssueAlertJob::class, 1);
    expect(AlertHistory::query()->count())->toBe(1);
});

it('does not dispatch another alert while the rule is cooling down', function () {
    Queue::fake([SendIssueAlertJob::class]);
    $this->travelTo('2026-08-27 12:00:00');
    $project = Project::factory()->create();
    AlertRule::factory()->recycle($project)->environment('production')->create([
        'cooldown_minutes' => 15,
    ]);
    $fingerprint = ['payment-processing'];

    processIncomingEvent($project, errorEventPayload([
        'event_id' => 'first',
        'fingerprint' => $fingerprint,
    ]));
    processIncomingEvent($project, errorEventPayload([
        'event_id' => 'second',
        'fingerprint' => $fingerprint,
    ]));

    Queue::assertPushed(SendIssueAlertJob::class, 1);

    $this->travelTo('2026-08-27 12:16:00');

    processIncomingEvent($project, errorEventPayload([
        'event_id' => 'third',
        'fingerprint' => $fingerprint,
    ]));

    Queue::assertPushed(SendIssueAlertJob::class, 2);
});
