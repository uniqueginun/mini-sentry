<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\Project;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final readonly class IssueService
{
    /**
     * @param  array<string, mixed>  $eventPayload
     */
    public function findOrCreateIssue(Project $project, string $fingerprint, array $eventPayload): Issue
    {
        $event = Arr::get($eventPayload, 'event', []);
        $event = is_array($event) ? $event : [];

        return $project->issues()->firstOrCreate(
            ['fingerprint' => $fingerprint],
            [
                'title' => $this->title($event),
                'culprit' => $this->culprit($event),
                'first_seen' => now(),
                'last_seen' => now(),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function title(array $event): string
    {
        $type = $this->stringValue(
            Arr::get($event, 'exception.values.0.type')
                ?? Arr::get($event, 'exception.type'),
        );
        $message = $this->stringValue(
            Arr::get($event, 'exception.values.0.message')
                ?? Arr::get($event, 'exception.values.0.value')
                ?? Arr::get($event, 'exception.value'),
        );

        $title = match (true) {
            $type !== null && $message !== null => $type.': '.$message,
            $type !== null => $type,
            default => 'Error',
        };

        return Str::take($title, 255);
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function culprit(array $event): ?string
    {
        $culprit = $this->stringValue(Arr::get($event, 'culprit'));

        return $culprit === null ? null : Str::take($culprit, 255);
    }

    private function stringValue(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
