<?php

namespace Database\Factories;

use App\Models\AlertHistory;
use App\Models\AlertRule;
use App\Models\Event;
use App\Models\Issue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AlertHistory>
 */
class AlertHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'alert_rule_id' => AlertRule::factory(),
            'issue_id' => Issue::factory(),
            'event_id' => Event::factory(),
            'fired_at' => now(),
        ];
    }
}
