<?php

namespace App\Actions\Dashboard;

use App\Enums\IssueSort;
use App\Http\Resources\IssueResource;
use App\Models\Event;
use App\Models\Issue;
use App\Models\IssueStat;
use App\Models\Team;

class GetDashboardOverview
{
    /**
     * @return array{
     *     stats: array{unresolved_issues: int, events_last_24h: int, affected_users: int},
     *     sparkline: list<int>,
     *     issues: list<array<string, mixed>>,
     *     has_projects: bool
     * }
     */
    public function handle(Team $team): array
    {
        return [
            'stats' => [
                'unresolved_issues' => Issue::query()->forTeam($team)->open()->count(),
                'events_last_24h' => Event::query()
                    ->forTeam($team)
                    ->where('occurred_at', '>=', now()->subDay())
                    ->count(),
                'affected_users' => (int) Issue::query()->forTeam($team)->open()->sum('user_count'),
            ],
            'sparkline' => $this->sparkline($team),
            'issues' => IssueResource::collection(
                Issue::query()
                    ->forTeam($team)
                    ->open()
                    ->with([
                        'project',
                        'latestEvent',
                        'stats' => fn ($query) => $query
                            ->where('bucket', '>=', Issue::sparklineWindowStart())
                            ->orderBy('bucket'),
                    ])
                    ->sortedBy(IssueSort::LastSeen)
                    ->limit(8)
                    ->get(),
            )->resolve(),
            'has_projects' => $team->projects()->exists(),
        ];
    }

    /**
     * @return list<int>
     */
    private function sparkline(Team $team): array
    {
        $start = Issue::sparklineWindowStart();
        $countsByBucket = IssueStat::query()
            ->where('bucket', '>=', $start)
            ->whereIn(
                'issue_id',
                Issue::query()->forTeam($team)->select('id'),
            )
            ->selectRaw('bucket, SUM(count) as total')
            ->groupBy('bucket')
            ->get()
            ->mapWithKeys(fn (IssueStat $stat): array => [
                $stat->bucket->startOfHour()->getTimestamp() => (int) $stat->total,
            ]);

        return collect(range(0, 23))
            ->map(function (int $offset) use ($start, $countsByBucket): int {
                $bucket = $start->addHours($offset);

                return (int) $countsByBucket->get($bucket->getTimestamp(), 0);
            })
            ->all();
    }
}
