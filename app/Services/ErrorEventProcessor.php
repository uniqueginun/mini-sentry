<?php

namespace App\Services;

use App\Enums\AlertRuleType;
use App\Enums\IssueStatus;
use App\Jobs\SendIssueAlertJob;
use App\Models\AlertHistory;
use App\Models\AlertRule;
use App\Models\Event;
use App\Models\Issue;
use App\Models\IssueStat;
use App\Models\IssueUser;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final readonly class ErrorEventProcessor
{
    /**
     * @param  array<string, mixed>  $eventPayload
     */
    public function process(Issue $issue, array $eventPayload): ?Event
    {
        $wasResolved = $issue->status === IssueStatus::Resolved;
        $isNewIssue = $issue->wasRecentlyCreated;

        /** @var array{event: Event|null, justRegressed: bool} $outcome */
        $outcome = DB::transaction(function () use ($issue, $eventPayload, $wasResolved): array {
            $event = $this->storeEvent($issue, $eventPayload);

            if ($event === null) {
                return [
                    'event' => null,
                    'justRegressed' => false,
                ];
            }

            $this->updateIssueStats($issue, $event);

            return [
                'event' => $event,
                'justRegressed' => $this->detectRegression($issue, $wasResolved),
            ];
        });

        $event = $outcome['event'];

        if (! $event instanceof Event) {
            return null;
        }

        $issue->refresh();

        try {
            $this->evaluateAlerts($issue, $event, $isNewIssue, $outcome['justRegressed']);
        } catch (Throwable $exception) {
            report($exception);
        }

        return $event;
    }

    /**
     * @param  array<string, mixed>  $eventPayload
     */
    public function storeEvent(Issue $issue, array $eventPayload): ?Event
    {
        $eventData = $this->eventData($eventPayload);
        $eventId = $this->stringValue(Arr::get($eventData, 'event_id'));

        if ($eventId === null) {
            return null;
        }

        $event = Event::query()->createOrFirst(
            [
                'project_id' => $issue->project_id,
                'event_id' => Str::take($eventId, 255),
            ],
            [
                'issue_id' => $issue->id,
                'exception_type' => $this->truncatedString($this->exceptionType($eventData), 255),
                'message' => $this->stringValue(
                    Arr::get($eventData, 'exception.values.0.message')
                        ?? Arr::get($eventData, 'exception.values.0.value')
                        ?? Arr::get($eventData, 'exception.value'),
                ),
                'environment' => $this->truncatedString($this->stringValue(Arr::get($eventData, 'environment')), 255),
                'release' => $this->truncatedString($this->stringValue(Arr::get($eventData, 'release')), 255),
                'user_identifier' => $this->userIdentifier($eventData),
                'occurred_at' => $this->occurredAt($eventData),
                'payload' => $eventPayload,
            ],
        );

        if (! $event->wasRecentlyCreated) {
            return null;
        }

        $tags = $this->tags($eventData);

        if ($tags !== []) {
            $event->tags()->createMany($tags);
        }

        return $event;
    }

    public function updateIssueStats(Issue $issue, Event $event): void
    {
        Issue::query()->whereKey($issue->id)->increment('event_count');

        Issue::query()
            ->whereKey($issue->id)
            ->where('first_seen', '>', $event->occurred_at)
            ->update(['first_seen' => $event->occurred_at]);

        Issue::query()
            ->whereKey($issue->id)
            ->where('last_seen', '<', $event->occurred_at)
            ->update(['last_seen' => $event->occurred_at]);

        IssueStat::query()->incrementOrCreate([
            'issue_id' => $issue->id,
            'bucket' => $event->occurred_at->startOfHour(),
        ]);

        if ($event->user_identifier === null) {
            return;
        }

        $inserted = IssueUser::query()->insertOrIgnore([
            'issue_id' => $issue->id,
            'identifier' => $event->user_identifier,
        ]);

        if ($inserted > 0) {
            Issue::query()->whereKey($issue->id)->increment('user_count');
        }
    }

    public function detectRegression(Issue $issue, bool $wasResolved): bool
    {
        if (! $wasResolved || $issue->status === IssueStatus::Regressed) {
            return false;
        }

        $regressedAt = now();

        $updated = Issue::query()
            ->whereKey($issue->id)
            ->where('status', IssueStatus::Resolved)
            ->update([
                'status' => IssueStatus::Regressed,
                'regressed_at' => $regressedAt,
            ]);

        if ($updated === 0) {
            return false;
        }

        return true;
    }

    public function evaluateAlerts(Issue $issue, Event $event, bool $isNewIssue, bool $justRegressed): void
    {
        $rules = AlertRule::query()
            ->where('project_id', $issue->project_id)
            ->where('enabled', true)
            ->where(function ($query) use ($event): void {
                $query->whereNull('environment')
                    ->orWhere('environment', $event->environment);
            })
            ->get();

        foreach ($rules as $rule) {
            if (! $this->ruleMatches($rule, $issue, $event, $isNewIssue, $justRegressed)) {
                continue;
            }

            if ($this->isCoolingDown($rule, $issue)) {
                continue;
            }

            AlertHistory::query()->create([
                'alert_rule_id' => $rule->id,
                'issue_id' => $issue->id,
                'event_id' => $event->id,
                'fired_at' => now(),
            ]);

            SendIssueAlertJob::dispatch($rule->id, $issue->id, $event->id);
        }
    }

    /**
     * @param  array<string, mixed>  $eventPayload
     * @return array<string, mixed>
     */
    private function eventData(array $eventPayload): array
    {
        $event = Arr::get($eventPayload, 'event', []);

        return is_array($event) ? $event : [];
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function exceptionType(array $event): ?string
    {
        return $this->stringValue(
            Arr::get($event, 'exception.values.0.type')
                ?? Arr::get($event, 'exception.type'),
        );
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function userIdentifier(array $event): ?string
    {
        $user = Arr::get($event, 'user');

        if (! is_array($user)) {
            return null;
        }

        foreach (['id', 'email', 'username'] as $key) {
            $value = $user[$key] ?? null;

            if (is_scalar($value) && trim((string) $value) !== '') {
                return Str::take((string) $value, 255);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function occurredAt(array $event): CarbonInterface
    {
        $timestamp = Arr::get($event, 'timestamp');

        if (! is_string($timestamp) && ! is_numeric($timestamp)) {
            return now();
        }

        try {
            return Date::parse((string) $timestamp);
        } catch (Throwable) {
            return now();
        }
    }

    /**
     * @param  array<string, mixed>  $event
     * @return list<array{key: string, value: string}>
     */
    private function tags(array $event): array
    {
        $tags = Arr::get($event, 'tags', []);

        if (! is_array($tags)) {
            return [];
        }

        $normalized = [];

        foreach ($tags as $key => $value) {
            if (is_array($value) && array_is_list($value) && count($value) >= 2) {
                $key = $value[0];
                $value = $value[1];
            } elseif (is_array($value)) {
                $key = $value['key'] ?? $key;
                $value = $value['value'] ?? null;
            }

            if (! is_string($key) && ! is_int($key)) {
                continue;
            }

            $key = trim((string) $key);

            if ($key === '' || ! is_scalar($value)) {
                continue;
            }

            $value = trim((string) $value);

            if ($value === '') {
                continue;
            }

            $normalized[] = [
                'key' => Str::take($key, 255),
                'value' => Str::take($value, 255),
            ];
        }

        return $normalized;
    }

    private function ruleMatches(AlertRule $rule, Issue $issue, Event $event, bool $isNewIssue, bool $justRegressed): bool
    {
        return match ($rule->type) {
            AlertRuleType::NewIssue => $isNewIssue,
            AlertRuleType::Regression => $justRegressed,
            AlertRuleType::EventFrequency => $this->exceedsEventFrequency($rule, $issue, $event),
            AlertRuleType::AffectedUsers => $rule->threshold !== null && $issue->user_count >= $rule->threshold,
            AlertRuleType::Environment => is_string($rule->environment)
                && $rule->environment !== ''
                && $event->environment === $rule->environment,
        };
    }

    private function exceedsEventFrequency(AlertRule $rule, Issue $issue, Event $event): bool
    {
        if ($rule->threshold === null || $rule->window_minutes === null) {
            return false;
        }

        $count = Event::query()
            ->where('issue_id', $issue->id)
            ->where('occurred_at', '>=', $event->occurred_at->subMinutes($rule->window_minutes))
            ->where('occurred_at', '<=', $event->occurred_at)
            ->count();

        return $count >= $rule->threshold;
    }

    private function isCoolingDown(AlertRule $rule, Issue $issue): bool
    {
        if ($rule->cooldown_minutes <= 0) {
            return false;
        }

        $lastFiredAt = AlertHistory::query()
            ->where('alert_rule_id', $rule->id)
            ->where('issue_id', $issue->id)
            ->latest('fired_at')
            ->value('fired_at');

        if ($lastFiredAt === null) {
            return false;
        }

        return Date::parse((string) $lastFiredAt)->greaterThan(now()->subMinutes($rule->cooldown_minutes));
    }

    private function stringValue(mixed $value): ?string
    {
        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function truncatedString(?string $value, int $limit): ?string
    {
        return $value === null ? null : Str::take($value, $limit);
    }
}
