<?php

namespace App\Models;

use Database\Factories\ProjectKeyFactory;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use LogicException;

/**
 * @property int $id
 * @property string $public_key
 * @property string $prefix
 * @property int $project_id
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property-read Project $project
 */
#[Fillable(['public_key', 'prefix', 'project_id', 'is_active'])]
#[Hidden(['public_key'])]
class ProjectKey extends Model implements Authenticatable
{
    /** @use HasFactory<ProjectKeyFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    public const PLAINTEXT_LENGTH = 32;

    public const PREFIX_LENGTH = 8;

    /**
     * The one-time plaintext key, available only immediately after generation.
     */
    public ?string $plaintext = null;

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ProjectKey $projectKey): void {
            if (static::isStoredHash($projectKey->public_key) && filled($projectKey->prefix)) {
                return;
            }

            $plaintext = $projectKey->plaintext;

            if (! is_string($plaintext) || $plaintext === '') {
                $plaintext = filled($projectKey->public_key) && ! static::isStoredHash($projectKey->public_key)
                    ? $projectKey->public_key
                    : static::generateUniquePlaintext();
            }

            $projectKey->forceFill([
                'prefix' => static::prefixFor($plaintext),
                'public_key' => static::hash($plaintext),
            ]);
            $projectKey->plaintext = $plaintext;
        });
    }

    /**
     * Hash a plaintext project key for storage and lookup.
     */
    public static function hash(string $plaintext): string
    {
        return hash('sha256', $plaintext);
    }

    /**
     * Find an active key by the bearer plaintext.
     */
    public static function findActiveByPlaintext(string $plaintext): ?self
    {
        return static::query()
            ->active()
            ->with(['project.team'])
            ->where('public_key', static::hash($plaintext))
            ->first();
    }

    /**
     * Generate a unique plaintext project key.
     */
    public static function generateUniquePlaintext(): string
    {
        do {
            $plaintext = Str::lower(Str::random(self::PLAINTEXT_LENGTH));
        } while (static::query()->where('public_key', static::hash($plaintext))->exists());

        return $plaintext;
    }

    /**
     * Get the display prefix for a plaintext key.
     */
    public static function prefixFor(string $plaintext): string
    {
        return Str::substr($plaintext, 0, self::PREFIX_LENGTH);
    }

    /**
     * Get the plaintext key that is only available immediately after generation.
     */
    public function plaintextOrFail(): string
    {
        if ($this->plaintext === null) {
            throw new LogicException('The project key plaintext is only available immediately after generation.');
        }

        return $this->plaintext;
    }

    /**
     * Get the project that owns the key.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Deactivate the key so it can no longer be used.
     */
    public function revoke(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Scope a query to only include active keys.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the name of the unique identifier for the key.
     */
    public function getAuthIdentifierName(): string
    {
        return $this->getKeyName();
    }

    /**
     * Get the unique identifier for the key.
     */
    public function getAuthIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Get the name of the password attribute for the key.
     */
    public function getAuthPasswordName(): string
    {
        return 'public_key';
    }

    /**
     * Get the password for the key.
     */
    public function getAuthPassword(): string
    {
        return $this->public_key;
    }

    /**
     * Get the token value for the "remember me" session.
     */
    public function getRememberToken(): ?string
    {
        return null;
    }

    /**
     * Set the token value for the "remember me" session.
     */
    public function setRememberToken($value): void {}

    /**
     * Get the column name for the "remember me" token.
     */
    public function getRememberTokenName(): string
    {
        return '';
    }

    /**
     * Determine if the value is already a stored SHA-256 hash.
     */
    protected static function isStoredHash(?string $value): bool
    {
        return is_string($value)
            && Str::length($value) === 64
            && ctype_xdigit($value);
    }
}
