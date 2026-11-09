<?php

namespace Database\Seeders;

use App\Models\AlarmEvent;
use App\Models\Device;
use App\Models\SensorReading;
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $device = Device::updateOrCreate(
            ['device_code' => 'ESP32-FIRE-01'],
            [
                'name' => 'ESP32 Fire Sensor - Ruang Utama',
                'is_online' => true,
                'last_heartbeat' => now(),
            ]
        );

        // Sample sensor readings
        SensorReading::create([
            'device_id' => $device->id,
            'mq2_value' => 125,
            'flame_detected' => false,
            'created_at' => now(),
        ]);

        // Sample alarm event history
        AlarmEvent::create([
            'device_id' => $device->id,
            'event_type' => 'SMOKE',
            'mq2_value' => 450,
            'status' => 'RESOLVED',
            'detected_at' => now()->subHours(5),
        ]);

        AlarmEvent::create([
            'device_id' => $device->id,
            'event_type' => 'FLAME',
            'mq2_value' => 280,
            'status' => 'RESOLVED',
            'detected_at' => now()->subHours(2),
        ]);

        AlarmEvent::create([
            'device_id' => $device->id,
            'event_type' => 'BOTH',
            'mq2_value' => 680,
            'status' => 'RESOLVED',
            'detected_at' => now()->subMinutes(30),
        ]);
    }
}
