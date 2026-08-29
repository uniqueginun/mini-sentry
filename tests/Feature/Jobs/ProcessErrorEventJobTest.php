<?php

use App\Enums\IssueStatus;
use App\Jobs\ProcessErrorEventJob;
use App\Jobs\SendIssueAlertJob;
use App\Models\AlertRule;
use App\Models\Event;
use App\Models\Issue;
use App\Models\Project;
use App\Services\ErrorEventProcessor;
use App\Services\FingerPrintCalculator;
use App\Services\IssueService;
use Illuminate\Support\Facades\Queue;

function handleErrorEvent(int $projectId, array $payload): void
{
    (new ProcessErrorEventJob($projectId, $payload))
        ->handle(new FingerPrintCalculator, new IssueService, new ErrorEventProcessor);
}

it('creates an issue when an error event is processed', function () {
    $project = Project::factory()->create();
    $payload = errorEventPayload([
        'fingerprint' => ['payment-processing', 'stripe-timeout'],
        'culprit' => 'CheckoutController::pay',
        'exception' => [
            'values' => [[
                'type' => 'RuntimeException',
                'message' => 'Stripe timeout',
            ]],
        ],
    ]);

    handleErrorEvent($project->id, $payload);

    $issue = $project->issues()->first();

    expect($project->issues()->count())->toBe(1);
    expect($issue)->not->toBeNull();
    expect($issue->fingerprint)->toBe(hash('sha256', $project->id.'|payment-processing|stripe-timeout'));
    expect($issue->title)->toBe('RuntimeException: Stripe timeout');
    expect($issue->culprit)->toBe('CheckoutController::pay');
    expect($issue->events()->count())->toBe(1);
    expect($issue->event_count)->toBe(1);
});

it('does not create a second issue when the same error is processed twice', function () {
    $project = Project::factory()->create();
    $payload = errorEventPayload([
        'event_id' => 'same-error',
        'fingerprint' => ['payment-processing'],
        'exception' => [
            'values' => [['type' => 'RuntimeException']],
        ],
    ]);

    handleErrorEvent($project->id, $payload);
    handleErrorEvent($project->id, $payload);

    expect($project->issues()->count())->toBe(1);
    expect(Event::query()->count())->toBe(1);
    expect($project->issues()->first()->event_count)->toBe(1);
});

it('stores a second unique event on the same issue', function () {
    $project = Project::factory()->create();
    $fingerprint = ['payment-processing'];

    handleErrorEvent($project->id, errorEventPayload([
        'event_id' => 'first',
        'fingerprint' => $fingerprint,
    ]));
    handleErrorEvent($project->id, errorEventPayload([
        'event_id' => 'second',
        'fingerprint' => $fingerprint,
    ]));

    $issue = $project->issues()->first();

    expect($project->issues()->count())->toBe(1);
    expect($issue->events()->count())->toBe(2);
    expect($issue->event_count)->toBe(2);
});

it('regresses a resolved issue when a new unique event arrives', function () {
    $project = Project::factory()->create();
    $payload = errorEventPayload(['fingerprint' => ['payment-processing']]);
    $fingerprint = (new FingerPrintCalculator)->calculate($project->id, $payload);
    Issue::factory()->recycle($project)->resolved()->create(['fingerprint' => $fingerprint]);

    handleErrorEvent($project->id, $payload);

    $issue = $project->issues()->first();

    expect($issue->status)->toBe(IssueStatus::Regressed);
    expect($issue->regressed_at)->not->toBeNull();
    expect($issue->events()->count())->toBe(1);
});

it('dispatches an alert job after a unique event is stored', function () {
    Queue::fake([SendIssueAlertJob::class]);
    $project = Project::factory()->create();
    $rule = AlertRule::factory()->recycle($project)->create();

    handleErrorEvent($project->id, errorEventPayload());

    Queue::assertPushed(fn (SendIssueAlertJob $job): bool => $job->alertRuleId === $rule->id);
});
