<?php

namespace App\Models;

use App\Enums\AlertRuleType;
use Database\Factories\AlertRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property AlertRuleType $type
 * @property string|null $environment
 * @property int|null $threshold
 * @property int|null $window_minutes
 * @property int $cooldown_minutes
 * @property bool $enabled
 * @property-read Project $project
 * @property-read Collection<int, AlertHistory> $histories
 */
#[Fillable(['project_id', 'name', 'type', 'environment', 'threshold', 'window_minutes', 'cooldown_minutes', 'enabled'])]
class AlertRule extends Model
{
    /** @use HasFactory<AlertRuleFactory> */
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
        'cooldown_minutes' => 15,
        'enabled' => true,
    ];

    /**
     * Get the project that owns the alert rule.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the fire history for the alert rule.
     *
     * @return HasMany<AlertHistory, $this>
     */
    public function histories(): HasMany
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
            'type' => AlertRuleType::class,
            'threshold' => 'integer',
            'window_minutes' => 'integer',
            'cooldown_minutes' => 'integer',
            'enabled' => 'boolean',
        ];
    }
}
