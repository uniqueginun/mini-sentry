<?php

namespace App\Models;

use Database\Factories\ReleaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property string $version
 * @property Carbon|null $created_at
 * @property-read Project $project
 */
#[Fillable(['project_id', 'version'])]
class Release extends Model
{
    /** @use HasFactory<ReleaseFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * Get the project that owns the release.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
