<?php

namespace Database\Factories;

use App\Models\AlarmEvent;
use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AlarmEvent>
 */
class AlarmEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'device_id' => Device::factory(),
            'event_type' => fake()->randomElement(['SMOKE', 'FLAME', 'BOTH']),
            'mq2_value' => fake()->numberBetween(350, 950),
            'status' => fake()->randomElement(['DANGER', 'RESOLVED']),
            'detected_at' => fake()->dateTimeBetween('-2 days', 'now'),
        ];
    }
}
