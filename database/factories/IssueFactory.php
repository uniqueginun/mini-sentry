<?php

namespace Database\Factories;

use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Issue>
 */
class IssueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $seenAt = now()->subHours(fake()->numberBetween(0, 48));

        return [
            'project_id' => Project::factory(),
            'fingerprint' => fake()->unique()->sha256(),
            'title' => fake()->sentence(),
            'culprit' => fake()->optional()->filePath(),
            'status' => IssueStatus::Unresolved,
            'first_seen' => $seenAt,
            'last_seen' => $seenAt,
            'regressed_at' => null,
            'event_count' => 1,
            'user_count' => 1,
        ];
    }

    /**
     * Indicate that the issue has been resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => IssueStatus::Resolved,
        ]);
    }

    /**
     * Indicate that the issue has regressed.
     */
    public function regressed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => IssueStatus::Regressed,
            'regressed_at' => now(),
        ]);
    }
}
