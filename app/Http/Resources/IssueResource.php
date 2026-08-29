<?php

namespace App\Http\Resources;

use App\Models\Issue;
use App\Models\IssueStat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Issue
 */
class IssueResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'culprit' => $this->culprit,
            'status' => $this->status->value,
            'event_count' => $this->event_count,
            'user_count' => $this->user_count,
            'first_seen' => $this->first_seen->toISOString(),
            'last_seen' => $this->last_seen->toISOString(),
            'level' => $this->relationLoaded('latestEvent')
                ? ($this->latestEvent?->level() ?? 'error')
                : 'error',
            'environment' => $this->relationLoaded('latestEvent')
                ? $this->latestEvent?->environment
                : null,
            'sparkline' => $this->sparkline(),
        ];
    }

    /**
     * @return list<int>
     */
    private function sparkline(): array
    {
        $start = Issue::sparklineWindowStart();
        $countsByBucket = $this->relationLoaded('stats')
            ? $this->stats->mapWithKeys(fn (IssueStat $stat): array => [
                $stat->bucket->startOfHour()->getTimestamp() => $stat->count,
            ])
            : collect();

        return collect(range(0, 23))
            ->map(function (int $offset) use ($start, $countsByBucket): int {
                $bucket = $start->addHours($offset);

                return (int) $countsByBucket->get($bucket->getTimestamp(), 0);
            })
            ->all();
    }
}
