<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property int $user_id
 * @property int $team_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Team $team
 * @property-read Collection<int, ProjectKey> $projectKeys
 * @property-read Collection<int, Issue> $issues
 * @property-read Collection<int, Event> $events
 * @property-read Collection<int, Release> $releases
 * @property-read Collection<int, AlertRule> $alertRules
 */
#[Fillable(['name', 'user_id', 'team_id'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    /**
     * Get the user who created the project.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team that owns the project.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the keys that belong to the project.
     *
     * @return HasMany<ProjectKey, $this>
     */
    public function projectKeys(): HasMany
    {
        return $this->hasMany(ProjectKey::class);
    }

    /**
     * Get the issues that belong to the project.
     *
     * @return HasMany<Issue, $this>
     */
    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    /**
     * Get the events that belong to the project.
     *
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Get the releases that belong to the project.
     *
     * @return HasMany<Release, $this>
     */
    public function releases(): HasMany
    {
        return $this->hasMany(Release::class);
    }

    /**
     * Get the alert rules that belong to the project.
     *
     * @return HasMany<AlertRule, $this>
     */
    public function alertRules(): HasMany
    {
        return $this->hasMany(AlertRule::class);
    }
}
