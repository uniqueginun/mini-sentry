<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Issue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $exceptionType = fake()->randomElement([
            'RuntimeException',
            'InvalidArgumentException',
            'ErrorException',
        ]);
        $message = fake()->sentence();

        return [
            'issue_id' => Issue::factory(),
            'project_id' => function (array $attributes): int {
                return Issue::query()->findOrFail($attributes['issue_id'])->project_id;
            },
            'event_id' => fake()->unique()->uuid(),
            'exception_type' => $exceptionType,
            'message' => $message,
            'environment' => fake()->randomElement(['production', 'staging', 'local']),
            'release' => fake()->semver(),
            'user_identifier' => null,
            'occurred_at' => now()->subMinutes(fake()->numberBetween(0, 120)),
            'payload' => [
                'exception' => [
                    'type' => $exceptionType,
                    'value' => $message,
                ],
            ],
        ];
    }
}
