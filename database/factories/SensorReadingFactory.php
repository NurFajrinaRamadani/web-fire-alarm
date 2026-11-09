<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\SensorReading;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SensorReading>
 */
class SensorReadingFactory extends Factory
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
            'mq2_value' => fake()->numberBetween(80, 500),
            'flame_detected' => fake()->boolean(10),
            'created_at' => now(),
        ];
    }
}
