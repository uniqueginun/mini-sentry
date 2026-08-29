<?php

namespace App\Models;

use Database\Factories\IssueUserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $issue_id
 * @property string $identifier
 * @property-read Issue $issue
 */
#[Fillable(['issue_id', 'identifier'])]
class IssueUser extends Model
{
    /** @use HasFactory<IssueUserFactory> */
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the issue that owns the affected user.
     *
     * @return BelongsTo<Issue, $this>
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }
}
