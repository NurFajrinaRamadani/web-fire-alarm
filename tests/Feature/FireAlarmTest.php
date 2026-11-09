<?php

namespace Tests\Feature;

use App\Models\AlarmEvent;
use App\Models\Device;
use App\Models\SensorReading;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FireAlarmTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the dashboard page renders successfully.
     */
    public function test_dashboard_renders_successfully(): void
    {
        $device = Device::factory()->create([
            'device_code' => 'ESP32-FIRE-01',
            'is_online' => true,
        ]);

        SensorReading::factory()->create([
            'device_id' => $device->id,
            'mq2_value' => 120,
            'flame_detected' => false,
        ]);

        AlarmEvent::factory()->create([
            'device_id' => $device->id,
            'event_type' => 'SMOKE',
            'mq2_value' => 450,
            'status' => 'DANGER',
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('FIRE ALARM SYSTEM');
        $response->assertSee('ESP32-FIRE-01');
        $response->assertSee('SMOKE');
    }

    /**
     * Test that dashboard status API returns valid JSON.
     */
    public function test_dashboard_status_api_returns_json(): void
    {
        $device = Device::factory()->create([
            'device_code' => 'ESP32-FIRE-01',
            'is_online' => true,
            'last_heartbeat' => now(),
        ]);

        SensorReading::factory()->create([
            'device_id' => $device->id,
            'mq2_value' => 150,
            'flame_detected' => false,
        ]);

        $response = $this->getJson('/api/v1/status');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'is_online' => true,
                'is_danger' => false,
                'system_status' => 'AMAN',
                'mq2_value' => 150,
                'flame_detected' => false,
            ]);
    }

    /**
     * Test IoT sensor-data API validates required payload.
     */
    public function test_sensor_data_api_validates_payload(): void
    {
        $response = $this->postJson('/api/v1/sensor-data', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['device_code', 'mq2_value', 'flame_detected']);
    }

    /**
     * Test IoT telemetry API saves sensor readings and triggers danger alarm.
     */
    public function test_sensor_data_api_creates_reading_and_danger_alarm(): void
    {
        $payload = [
            'device_code' => 'ESP32-FIRE-01',
            'mq2_value' => 550,
            'flame_detected' => true,
        ];

        $response = $this->postJson('/api/v1/sensor-data', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Data processed successfully',
            ]);

        $this->assertDatabaseHas('devices', [
            'device_code' => 'ESP32-FIRE-01',
            'is_online' => true,
        ]);

        $this->assertDatabaseHas('sensor_readings', [
            'mq2_value' => 550,
            'flame_detected' => true,
        ]);

        $this->assertDatabaseHas('alarm_events', [
            'event_type' => 'BOTH',
            'status' => 'DANGER',
        ]);
    }

    /**
     * Test danger event de-duplication: continuous danger does not spam alarm_events table every second.
     */
    public function test_continuous_danger_does_not_create_duplicate_alarm_events(): void
    {
        $device = Device::factory()->create([
            'device_code' => 'ESP32-FIRE-01',
        ]);

        $payload = [
            'device_code' => 'ESP32-FIRE-01',
            'mq2_value' => 600,
            'flame_detected' => true,
        ];

        // Send 1st danger ping
        $this->postJson('/api/v1/sensor-data', $payload)->assertStatus(200);
        $this->assertEquals(1, AlarmEvent::where('device_id', $device->id)->where('status', 'DANGER')->count());

        // Send 2nd danger ping (same event_type: BOTH)
        $this->postJson('/api/v1/sensor-data', $payload)->assertStatus(200);

        // Send 3rd danger ping
        $this->postJson('/api/v1/sensor-data', $payload)->assertStatus(200);

        // Count should still be 1 (preventing duplicate repetitive alarms)
        $this->assertEquals(1, AlarmEvent::where('device_id', $device->id)->where('status', 'DANGER')->count());

        // But sensor_readings should record all 3 telemetry pings
        $this->assertEquals(3, SensorReading::where('device_id', $device->id)->count());
    }

    /**
     * Test resolving an alarm event.
     */
    public function test_alarm_event_can_be_resolved(): void
    {
        $device = Device::factory()->create();
        $alarm = AlarmEvent::factory()->create([
            'device_id' => $device->id,
            'status' => 'DANGER',
        ]);

        $response = $this->postJson("/api/v1/alarm-events/{$alarm->id}/resolve");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Alarm event marked as resolved',
            ]);

        $this->assertDatabaseHas('alarm_events', [
            'id' => $alarm->id,
            'status' => 'RESOLVED',
        ]);
    }
}
