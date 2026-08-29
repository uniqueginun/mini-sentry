<?php

namespace App\Models;

use Database\Factories\IssueStatFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $issue_id
 * @property Carbon $bucket
 * @property int $count
 * @property-read Issue $issue
 */
#[Fillable(['issue_id', 'bucket', 'count'])]
class IssueStat extends Model
{
    /** @use HasFactory<IssueStatFactory> */
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
        'count' => 0,
    ];

    /**
     * Get the issue that owns the stat.
     *
     * @return BelongsTo<Issue, $this>
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bucket' => 'datetime',
            'count' => 'integer',
        ];
    }
}
