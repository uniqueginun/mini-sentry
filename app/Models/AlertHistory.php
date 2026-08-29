<?php

namespace App\Models;

use Database\Factories\AlertHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $alert_rule_id
 * @property int $issue_id
 * @property string|null $event_id
 * @property Carbon $fired_at
 * @property-read AlertRule $alertRule
 * @property-read Issue $issue
 * @property-read Event|null $event
 */
#[Fillable(['alert_rule_id', 'issue_id', 'event_id', 'fired_at'])]
class AlertHistory extends Model
{
    /** @use HasFactory<AlertHistoryFactory> */
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the alert rule that fired.
     *
     * @return BelongsTo<AlertRule, $this>
     */
    public function alertRule(): BelongsTo
    {
        return $this->belongsTo(AlertRule::class);
    }

    /**
     * Get the issue the alert was fired for.
     *
     * @return BelongsTo<Issue, $this>
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    /**
     * Get the event that triggered the alert.
     *
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fired_at' => 'datetime',
        ];
    }
}
