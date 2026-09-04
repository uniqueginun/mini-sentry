<?php

namespace App\Models;

use App\Enums\IssueSort;
use App\Enums\IssueStatus;
use Carbon\CarbonInterface;
use Database\Factories\IssueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property string $fingerprint
 * @property string $title
 * @property string|null $culprit
 * @property IssueStatus $status
 * @property Carbon $first_seen
 * @property Carbon $last_seen
 * @property Carbon|null $regressed_at
 * @property int $event_count
 * @property int $user_count
 * @property-read Project $project
 * @property-read Collection<int, Event> $events
 * @property-read Event|null $latestEvent
 * @property-read Collection<int, IssueStat> $stats
 * @property-read Collection<int, IssueUser> $users
 * @property-read Collection<int, AlertHistory> $alertHistories
 */
#[Fillable(['project_id', 'fingerprint', 'title', 'culprit', 'status', 'first_seen', 'last_seen', 'regressed_at', 'event_count', 'user_count'])]
class Issue extends Model
{
    /** @use HasFactory<IssueFactory> */
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => IssueStatus::Unresolved->value,
        'event_count' => 0,
        'user_count' => 0,
    ];

    /**
     * Get the project that owns the issue.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the events that belong to the issue.
     *
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Get the most recently occurred event for the issue.
     *
     * @return HasOne<Event, $this>
     */
    public function latestEvent(): HasOne
    {
        return $this->hasOne(Event::class)->latestOfMany('occurred_at');
    }

    /**
     * Get the time-bucketed stats for the issue.
     *
     * @return HasMany<IssueStat, $this>
     */
    public function stats(): HasMany
    {
        return $this->hasMany(IssueStat::class);
    }

    /**
     * Get the unique affected users for the issue.
     *
     * @return HasMany<IssueUser, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(IssueUser::class);
    }

    /**
     * Get the alert history for the issue.
     *
     * @return HasMany<AlertHistory, $this>
     */
    public function alertHistories(): HasMany
    {
        return $this->hasMany(AlertHistory::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => IssueStatus::class,
            'first_seen' => 'datetime',
            'last_seen' => 'datetime',
            'regressed_at' => 'datetime',
            'event_count' => 'integer',
            'user_count' => 'integer',
        ];
    }

    /**
     * @param  Builder<Issue>  $query
     * @return Builder<Issue>
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): Builder
    {
        if ($term === null || $term === '') {
            return $query;
        }

        $like = '%'.addcslashes($term, '%_\\').'%';

        return $query->where(function (Builder $query) use ($like): void {
            $query->whereLike('title', $like)
                ->orWhereLike('culprit', $like);
        });
    }

    /**
     * @param  Builder<Issue>  $query
     * @return Builder<Issue>
     */
    #[Scope]
    protected function statusFilter(Builder $query, string $status): Builder
    {
        if ($status === 'all') {
            return $query;
        }

        return $query->where('status', $status);
    }

    /**
     * @param  Builder<Issue>  $query
     * @return Builder<Issue>
     */
    #[Scope]
    protected function forEnvironment(Builder $query, ?string $environment): Builder
    {
        if ($environment === null || $environment === '') {
            return $query;
        }

        return $query->whereHas(
            'events',
            fn (Builder $events): Builder => $events->where('environment', $environment),
        );
    }

    /**
     * @param  Builder<Issue>  $query
     * @return Builder<Issue>
     */
    #[Scope]
    protected function forRelease(Builder $query, ?string $release): Builder
    {
        if ($release === null || $release === '') {
            return $query;
        }

        return $query->whereHas(
            'events',
            fn (Builder $events): Builder => $events->where('release', $release),
        );
    }

    /**
     * @param  Builder<Issue>  $query
     * @return Builder<Issue>
     */
    #[Scope]
    protected function sortedBy(Builder $query, IssueSort $sort): Builder
    {
        return $query->orderByDesc($sort->column())->orderByDesc($query->qualifyColumn('id'));
    }

    /**
     * @param  Builder<Issue>  $query
     * @return Builder<Issue>
     */
    #[Scope]
    protected function forTeam(Builder $query, Team $team): Builder
    {
        return $query->whereIn(
            $query->qualifyColumn('project_id'),
            $team->projects()->select('id'),
        );
    }

    /**
     * @param  Builder<Issue>  $query
     * @return Builder<Issue>
     */
    #[Scope]
    protected function open(Builder $query): Builder
    {
        return $query->where('status', '!=', IssueStatus::Resolved);
    }

    /**
     * The start of the 24 hourly buckets shown on issue graphs.
     */
    public static function sparklineWindowStart(): CarbonInterface
    {
        return now()->subHours(23)->startOfHour();
    }
}
