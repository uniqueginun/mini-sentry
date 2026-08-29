<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'user_id' => User::factory(),
            'team_id' => function (array $attributes): int {
                $user = User::query()->findOrFail($attributes['user_id']);

                return $user->current_team_id ?? Team::factory()->create()->id;
            },
        ];
    }
}
