<?php

namespace Database\Factories;

use App\Models\Issue;
use App\Models\IssueStat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IssueStat>
 */
class IssueStatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'issue_id' => Issue::factory(),
            'bucket' => now()->startOfHour()->subHours(fake()->unique()->numberBetween(0, 50000)),
            'count' => fake()->numberBetween(1, 50),
        ];
    }
}
