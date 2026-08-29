<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventTag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventTag>
 */
class EventTagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'key' => fake()->randomElement(['environment', 'browser', 'os', 'server_name']),
            'value' => fake()->word(),
        ];
    }
}
