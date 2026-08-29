<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final readonly class FingerPrintCalculator
{
    /**
     * @param  array<string, mixed>  $eventPayload
     */
    public function calculate(int $projectId, array $eventPayload): string
    {
        $event = Arr::get($eventPayload, 'event', []);
        $event = is_array($event) ? $event : [];

        $parts = $this->clientParts($event) ?? $this->defaultParts($event);

        $canonical = collect([$projectId, ...$parts])
            ->map(fn (int|string $part): string => $this->stripLineNumber((string) $part))
            ->reject(fn (string $part): bool => $part === '')
            ->implode('|');

        return (string) Str::of($canonical)->hash(algorithm: 'sha256');
    }

    /**
     * @param  array<string, mixed>  $event
     * @return list<string>|null
     */
    private function clientParts(array $event): ?array
    {
        $fingerprint = Arr::get($event, 'fingerprint');

        if (! is_array($fingerprint)) {
            return null;
        }

        $parts = array_values(array_filter(
            $fingerprint,
            fn (mixed $part): bool => is_string($part) && $part !== '',
        ));

        return $parts === [] ? null : $parts;
    }

    /**
     * @param  array<string, mixed>  $event
     * @return list<string>
     */
    private function defaultParts(array $event): array
    {
        $type = Arr::get($event, 'exception.values.0.type')
            ?? Arr::get($event, 'exception.type');

        [$class, $method] = $this->applicationLocation($event);

        return [
            is_string($type) ? $type : '',
            $class,
            $method,
        ];
    }

    /**
     * @param  array<string, mixed>  $event
     * @return array{0: string, 1: string}
     */
    private function applicationLocation(array $event): array
    {
        $inAppFrame = $this->firstApplicationFrame($event);

        if ($inAppFrame !== null) {
            return $this->frameLocation($inAppFrame);
        }

        $culprit = $this->parseCulprit(Arr::get($event, 'culprit'));

        if ($culprit[0] !== '' || $culprit[1] !== '') {
            return $culprit;
        }

        $frame = $this->firstLocatedFrame($event);

        return $frame === null ? ['', ''] : $this->frameLocation($frame);
    }

    /**
     * @param  array<string, mixed>  $event
     * @return array<string, mixed>|null
     */
    private function firstApplicationFrame(array $event): ?array
    {
        return $this->firstFrame(
            $event,
            fn (array $frame): bool => ($frame['in_app'] ?? false) === true
                && $this->frameHasLocation($frame),
        );
    }

    /**
     * @param  array<string, mixed>  $event
     * @return array<string, mixed>|null
     */
    private function firstLocatedFrame(array $event): ?array
    {
        return $this->firstFrame(
            $event,
            fn (array $frame): bool => $this->frameHasLocation($frame),
        );
    }

    /**
     * @param  array<string, mixed>  $event
     * @param  callable(array<string, mixed>): bool  $matcher
     * @return array<string, mixed>|null
     */
    private function firstFrame(array $event, callable $matcher): ?array
    {
        $frames = Arr::get($event, 'exception.values.0.stacktrace', []);

        if (! is_array($frames)) {
            return null;
        }

        $frame = Arr::first(
            $frames,
            fn (mixed $frame): bool => is_array($frame) && $matcher($frame),
        );

        return is_array($frame) ? $frame : null;
    }

    /**
     * @param  array<string, mixed>  $frame
     */
    private function frameHasLocation(array $frame): bool
    {
        return is_string($frame['class'] ?? null)
            && $frame['class'] !== ''
            && is_string($frame['function'] ?? null)
            && $frame['function'] !== '';
    }

    /**
     * @param  array<string, mixed>  $frame
     * @return array{0: string, 1: string}
     */
    private function frameLocation(array $frame): array
    {
        return [
            is_string($frame['class'] ?? null) ? $frame['class'] : '',
            is_string($frame['function'] ?? null) ? $frame['function'] : '',
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseCulprit(mixed $culprit): array
    {
        if (! is_string($culprit) || $culprit === '') {
            return ['', ''];
        }

        if (! preg_match('/^(?<class>.+)::(?<method>[^:]+)(?::\d+)?$/', $culprit, $matches)) {
            return ['', ''];
        }

        return [$matches['class'], $matches['method']];
    }

    private function stripLineNumber(string $part): string
    {
        if (! str_contains($part, '::')) {
            return $part;
        }

        return (string) Str::of($part)->replaceMatches('/:\d+$/', '');
    }
}
