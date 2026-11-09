<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AlarmEvent;
use App\Models\Device;
use App\Models\SensorReading;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FireAlarmController extends Controller
{
    /**
     * MQ-2 smoke/gas danger threshold value.
     */
    public const MQ2_THRESHOLD = 400;

    /**
     * Store incoming IoT telemetry from ESP32.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_code' => ['required', 'string'],
            'mq2_value' => ['required', 'integer'],
            'flame_detected' => ['required', 'boolean'],
        ]);

        // a. Find or create device and update online & heartbeat status
        $device = Device::firstOrCreate(
            ['device_code' => $validated['device_code']],
            ['name' => 'ESP32 Device '.$validated['device_code']]
        );

        $device->update([
            'is_online' => true,
            'last_heartbeat' => now(),
        ]);

        // b. Store sensor reading
        SensorReading::create([
            'device_id' => $device->id,
            'mq2_value' => $validated['mq2_value'],
            'flame_detected' => $validated['flame_detected'],
            'created_at' => now(),
        ]);

        // c. Danger logic: flame_detected == true OR mq2_value > 400
        $isFlameDanger = (bool) $validated['flame_detected'];
        $isSmokeDanger = $validated['mq2_value'] > self::MQ2_THRESHOLD;

        if ($isFlameDanger || $isSmokeDanger) {
            $eventType = match (true) {
                $isFlameDanger && $isSmokeDanger => 'BOTH',
                $isFlameDanger => 'FLAME',
                default => 'SMOKE',
            };

            // Check if active danger already exists for this device
            $activeDanger = AlarmEvent::where('device_id', $device->id)
                ->where('status', 'DANGER')
                ->latest('id')
                ->first();

            // Record only if no active danger or the danger event type has escalated/changed
            if (! $activeDanger || $activeDanger->event_type !== $eventType) {
                AlarmEvent::create([
                    'device_id' => $device->id,
                    'event_type' => $eventType,
                    'mq2_value' => $validated['mq2_value'],
                    'status' => 'DANGER',
                    'detected_at' => now(),
                ]);
            }
        }

        // d. Return standard success response
        return response()->json([
            'status' => 'success',
            'message' => 'Data processed successfully',
        ], 200);
    }

    /**
     * Mark an alarm event as resolved.
     */
    public function resolve(AlarmEvent $alarmEvent): JsonResponse
    {
        $alarmEvent->update([
            'status' => 'RESOLVED',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Alarm event marked as resolved',
        ], 200);
    }
}
