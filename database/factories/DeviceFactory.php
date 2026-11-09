<?php

namespace Database\Factories;

use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'device_code' => 'ESP32-'.strtoupper(fake()->bothify('??-####')),
            'name' => fake()->randomElement(['Sensor Ruang Server', 'Sensor Dapur', 'Sensor Gudang', 'Sensor Laboratorium']),
            'is_online' => fake()->boolean(80),
            'last_heartbeat' => now(),
        ];
    }
}
