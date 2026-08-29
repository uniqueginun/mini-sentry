<?php

namespace App\Models;

use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property int $issue_id
 * @property int $project_id
 * @property string $event_id
 * @property string|null $exception_type
 * @property string|null $message
 * @property string|null $environment
 * @property string|null $release
 * @property string|null $user_identifier
 * @property Carbon $occurred_at
 * @property array<string, mixed> $payload
 * @property-read Issue $issue
 * @property-read Project $project
 * @property-read Collection<int, EventTag> $tags
 */
#[Fillable(['issue_id', 'project_id', 'event_id', 'exception_type', 'message', 'environment', 'release', 'user_identifier', 'occurred_at', 'payload'])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, HasUuids;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the issue that owns the event.
     *
     * @return BelongsTo<Issue, $this>
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    /**
     * Get the project that owns the event.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the tags that belong to the event.
     *
     * @return HasMany<EventTag, $this>
     */
    public function tags(): HasMany
    {
        return $this->hasMany(EventTag::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    /**
     * Get the severity level recorded on the event payload.
     */
    public function level(): string
    {
        $level = $this->eventPayload()['level'] ?? null;

        if (! is_string($level) || $level === '') {
            return 'error';
        }

        return Str::lower($level);
    }

    /**
     * @return list<array{filename: string|null, lineno: int|null, function: string|null, class: string|null, in_app: bool, is_culprit: bool}>
     */
    public function stackFrames(): array
    {
        $stacktrace = Arr::get($this->eventPayload(), 'exception.values.0.stacktrace', []);

        if (! is_array($stacktrace)) {
            return [];
        }

        if (isset($stacktrace['frames']) && is_array($stacktrace['frames'])) {
            $stacktrace = $stacktrace['frames'];
        }

        $frames = [];

        foreach ($stacktrace as $frame) {
            if (! is_array($frame)) {
                continue;
            }

            $lineno = $frame['lineno'] ?? $frame['line'] ?? null;

            $frames[] = [
                'filename' => $this->nullableString($frame['filename'] ?? $frame['file'] ?? null),
                'lineno' => is_numeric($lineno) ? (int) $lineno : null,
                'function' => $this->nullableString($frame['function'] ?? null),
                'class' => $this->nullableString($frame['class'] ?? null),
                'in_app' => ($frame['in_app'] ?? false) === true,
                'is_culprit' => false,
            ];
        }

        $frames = array_reverse($frames);
        $culpritIndex = $this->culpritFrameIndex($frames);

        foreach ($frames as $index => $frame) {
            $frames[$index]['is_culprit'] = $index === $culpritIndex;
        }

        return $frames;
    }

    /**
     * @return list<array{timestamp: string|null, category: string|null, message: string|null, level: string|null, type: string|null}>
     */
    public function breadcrumbs(): array
    {
        $breadcrumbs = Arr::get($this->eventPayload(), 'breadcrumbs.values')
            ?? Arr::get($this->eventPayload(), 'breadcrumbs', []);

        if (! is_array($breadcrumbs)) {
            return [];
        }

        $items = [];

        foreach ($breadcrumbs as $crumb) {
            if (! is_array($crumb)) {
                continue;
            }

            $timestamp = $crumb['timestamp'] ?? null;

            $items[] = [
                'timestamp' => is_string($timestamp) || is_numeric($timestamp) ? (string) $timestamp : null,
                'category' => $this->nullableString($crumb['category'] ?? null),
                'message' => $this->nullableString($crumb['message'] ?? null),
                'level' => $this->nullableString($crumb['level'] ?? null),
                'type' => $this->nullableString($crumb['type'] ?? null),
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    public function eventPayload(): array
    {
        $payload = $this->payload;
        $event = $payload['event'] ?? $payload;

        return is_array($event) ? $event : [];
    }

    /**
     * @param  list<array{filename: string|null, lineno: int|null, function: string|null, class: string|null, in_app: bool, is_culprit: bool}>  $frames
     */
    private function culpritFrameIndex(array $frames): ?int
    {
        if ($frames === []) {
            return null;
        }

        $count = count($frames);
        $innermostFirst = $this->isInnermostFirst($frames);
        $order = $innermostFirst
            ? range(0, $count - 1)
            : range($count - 1, 0, -1);

        foreach ($order as $index) {
            if ($this->isCulpritCandidate($frames[$index])) {
                return $index;
            }
        }

        foreach ($order as $index) {
            $filename = $frames[$index]['filename'] ?? null;

            if (($frames[$index]['in_app'] ?? false) === true && ! $this->isVendorPath($filename)) {
                return $index;
            }
        }

        return $innermostFirst ? 0 : $count - 1;
    }

    /**
     * @param  list<array{filename: string|null, lineno: int|null, function: string|null, class: string|null, in_app: bool, is_culprit: bool}>  $frames
     */
    private function isInnermostFirst(array $frames): bool
    {
        $first = $frames[0]['filename'] ?? null;
        $last = $frames[array_key_last($frames)]['filename'] ?? null;

        if ($this->isEntryPoint($first) && ! $this->isEntryPoint($last)) {
            return false;
        }

        if ($this->isEntryPoint($last) && ! $this->isEntryPoint($first)) {
            return true;
        }

        return true;
    }

    /**
     * @param  array{filename: string|null, lineno: int|null, function: string|null, class: string|null, in_app: bool, is_culprit: bool}  $frame
     */
    private function isCulpritCandidate(array $frame): bool
    {
        $filename = $frame['filename'] ?? null;

        if ($this->isVendorPath($filename) || $this->isEntryPoint($filename)) {
            return false;
        }

        if ($this->isApplicationPath($filename)) {
            return true;
        }

        return ($frame['in_app'] ?? false) === true;
    }

    private function isVendorPath(?string $filename): bool
    {
        if ($filename === null) {
            return false;
        }

        return Str::contains($filename, ['/vendor/', '\\vendor\\']);
    }

    private function isApplicationPath(?string $filename): bool
    {
        if ($filename === null) {
            return false;
        }

        return Str::contains($filename, ['/app/', '\\app\\']);
    }

    private function isEntryPoint(?string $filename): bool
    {
        if ($filename === null) {
            return false;
        }

        $normalized = (string) Str::of($filename)->replace('\\', '/');

        return Str::endsWith($normalized, '/public/index.php')
            || Str::endsWith($normalized, '/artisan')
            || $normalized === 'index.php'
            || $normalized === 'artisan';
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
