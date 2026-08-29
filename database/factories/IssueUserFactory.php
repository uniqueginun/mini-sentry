<?php

namespace Database\Factories;

use App\Models\Issue;
use App\Models\IssueUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IssueUser>
 */
class IssueUserFactory extends Factory
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
            'identifier' => fake()->unique()->userName(),
        ];
    }
}
