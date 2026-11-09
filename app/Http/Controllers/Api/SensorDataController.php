<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DashboardController;
use App\Models\AlarmEvent;
use App\Models\Device;
use App\Models\SensorReading;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SensorDataController extends Controller
{
    /**
     * Store telemetry received from an IoT device (e.g. ESP32).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_code' => ['required', 'string', 'max:50'],
            'mq2_value' => ['required', 'integer', 'min:0'],
            'flame_detected' => ['required', 'boolean'],
        ]);

        $device = Device::firstOrCreate(
            ['device_code' => $validated['device_code']],
            ['name' => 'ESP32 Device '.$validated['device_code']]
        );

        $device->update([
            'is_online' => true,
            'last_heartbeat' => now(),
        ]);

        $reading = SensorReading::create([
            'device_id' => $device->id,
            'mq2_value' => $validated['mq2_value'],
            'flame_detected' => $validated['flame_detected'],
            'created_at' => now(),
        ]);

        $mq2Danger = $validated['mq2_value'] >= DashboardController::MQ2_THRESHOLD;
        $flameDanger = (bool) $validated['flame_detected'];

        $alarmCreated = null;
        if ($mq2Danger || $flameDanger) {
            $eventType = match (true) {
                $mq2Danger && $flameDanger => 'BOTH',
                $flameDanger => 'FLAME',
                default => 'SMOKE',
            };

            $alarmCreated = AlarmEvent::create([
                'device_id' => $device->id,
                'event_type' => $eventType,
                'mq2_value' => $validated['mq2_value'],
                'status' => 'DANGER',
                'detected_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sensor reading recorded successfully.',
            'device' => $device->only(['id', 'device_code', 'is_online', 'last_heartbeat']),
            'reading' => $reading,
            'alarm_event' => $alarmCreated,
        ], 201);
    }

    /**
     * Update heartbeat status for an IoT device.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_code' => ['required', 'string', 'max:50'],
        ]);

        $device = Device::firstOrCreate(
            ['device_code' => $validated['device_code']],
            ['name' => 'ESP32 Device '.$validated['device_code']]
        );

        $device->update([
            'is_online' => true,
            'last_heartbeat' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Heartbeat received.',
            'last_heartbeat' => $device->last_heartbeat,
        ]);
    }

    /**
     * Mark an alarm event as resolved.
     */
    public function resolveAlarm(AlarmEvent $alarmEvent): JsonResponse
    {
        $alarmEvent->update([
            'status' => 'RESOLVED',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Alarm event marked as resolved.',
            'alarm_event' => $alarmEvent,
        ]);
    }
}
