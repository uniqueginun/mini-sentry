<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectKey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectKey>
 */
class ProjectKeyFactory extends Factory
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
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the key is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
