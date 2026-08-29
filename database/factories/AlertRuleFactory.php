<?php

namespace Database\Factories;

use App\Enums\AlertRuleType;
use App\Models\AlertRule;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AlertRule>
 */
class AlertRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => 'New issue',
            'type' => AlertRuleType::NewIssue,
            'environment' => null,
            'threshold' => null,
            'window_minutes' => null,
            'cooldown_minutes' => 15,
            'enabled' => true,
        ];
    }

    /**
     * Indicate that the rule fires when an issue regresses.
     */
    public function regression(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Regression detected',
            'type' => AlertRuleType::Regression,
        ]);
    }

    /**
     * Indicate that the rule fires when event volume exceeds a threshold.
     */
    public function eventFrequency(int $threshold = 100, int $windowMinutes = 5): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Event frequency',
            'type' => AlertRuleType::EventFrequency,
            'threshold' => $threshold,
            'window_minutes' => $windowMinutes,
        ]);
    }

    /**
     * Indicate that the rule fires when unique affected users exceed a threshold.
     */
    public function affectedUsers(int $threshold = 20): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Affected users',
            'type' => AlertRuleType::AffectedUsers,
            'threshold' => $threshold,
        ]);
    }

    /**
     * Indicate that the rule fires when an event occurs in an environment.
     */
    public function environment(string $environment = 'production'): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Error in '.$environment,
            'type' => AlertRuleType::Environment,
            'environment' => $environment,
        ]);
    }

    /**
     * Indicate that the rule is disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'enabled' => false,
        ]);
    }
}
