<?php

namespace App\Http\Controllers;

use App\Models\AlarmEvent;
use App\Models\Device;
use App\Models\SensorReading;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * MQ-2 smoke/gas danger threshold value.
     */
    public const MQ2_THRESHOLD = 400;

    /**
     * Display the fire alarm monitoring dashboard.
     */
    public function index(): View
    {
        $device = Device::with('latestSensorReading')->first();
        $latestReading = $device?->latestSensorReading ?? SensorReading::latest('id')->first();
        $alarmEvents = AlarmEvent::with('device')->latest('detected_at')->take(15)->get();

        $recentReadings = $device
            ? SensorReading::where('device_id', $device->id)->latest('id')->take(15)->get()->reverse()->values()
            : SensorReading::latest('id')->take(15)->get()->reverse()->values();

        $mq2Value = (int) ($latestReading?->mq2_value ?? 0);
        $flameDetected = (bool) ($latestReading?->flame_detected ?? false);
        $smokeDetected = $mq2Value > self::MQ2_THRESHOLD;
        $isDanger = $flameDetected || $smokeDetected;

        $isOnline = false;
        if ($device && $device->is_online) {
            $isOnline = $device->last_heartbeat ? $device->last_heartbeat->diffInMinutes(now()) <= 5 : true;
        }

        return view('dashboard', [
            'device' => $device,
            'latestReading' => $latestReading,
            'recentReadings' => $recentReadings,
            'alarmEvents' => $alarmEvents,
            'mq2Value' => $mq2Value,
            'mq2Threshold' => self::MQ2_THRESHOLD,
            'flameDetected' => $flameDetected,
            'smokeDetected' => $smokeDetected,
            'isDanger' => $isDanger,
            'isOnline' => $isOnline,
        ]);
    }

    /**
     * Get real-time dashboard data in JSON format for live updates.
     */
    public function getStatus(): JsonResponse
    {
        $device = Device::with('latestSensorReading')->first();
        $latestReading = $device?->latestSensorReading ?? SensorReading::latest('id')->first();
        $alarmEvents = AlarmEvent::with('device')->latest('detected_at')->take(15)->get();

        $recentReadings = $device
            ? SensorReading::where('device_id', $device->id)->latest('id')->take(15)->get()->reverse()->values()
            : SensorReading::latest('id')->take(15)->get()->reverse()->values();

        $mq2Value = (int) ($latestReading?->mq2_value ?? 0);
        $flameDetected = (bool) ($latestReading?->flame_detected ?? false);
        $smokeDetected = $mq2Value > self::MQ2_THRESHOLD;
        $isDanger = $flameDetected || $smokeDetected;

        $dangerType = null;
        if ($isDanger) {
            $dangerType = match (true) {
                $flameDetected && $smokeDetected => 'BOTH',
                $flameDetected => 'FLAME',
                default => 'SMOKE',
            };
        }

        $isOnline = false;
        if ($device && $device->is_online) {
            $isOnline = $device->last_heartbeat ? $device->last_heartbeat->diffInMinutes(now()) <= 5 : true;
        }

        return response()->json([
            'status' => 'success',
            'is_online' => $isOnline,
            'is_danger' => $isDanger,
            'system_status' => $isDanger ? 'BAHAYA' : 'AMAN',
            'danger_type' => $dangerType,
            'device_name' => $device?->name ?? 'Belum Terdaftar',
            'device_code' => $device?->device_code ?? '-',
            'mq2_value' => $mq2Value,
            'mq2_threshold' => self::MQ2_THRESHOLD,
            'flame_detected' => $flameDetected,
            'last_reading_time' => $latestReading?->created_at?->diffForHumans() ?? 'Belum ada data',
            'last_reading_full' => $latestReading?->created_at?->format('d/m/Y H:i:s') ?? '-',
            'last_heartbeat' => $device?->last_heartbeat ? $device->last_heartbeat->format('H:i:s').' ('.$device->last_heartbeat->diffForHumans().')' : '-',
            'recent_readings' => $recentReadings->map(fn ($r) => [
                'time' => $r->created_at ? $r->created_at->format('H:i:s') : '',
                'mq2' => $r->mq2_value,
                'flame' => (bool) $r->flame_detected,
            ]),
            'alarm_events' => $alarmEvents->map(fn ($event, $idx) => [
                'id' => $event->id,
                'no' => $idx + 1,
                'detected_at_formatted' => $event->detected_at ? $event->detected_at->format('d/m/Y H:i:s') : '-',
                'detected_at_diff' => $event->detected_at ? $event->detected_at->diffForHumans() : '',
                'device_name' => $event->device?->name ?? 'Perangkat IoT',
                'device_code' => $event->device?->device_code ?? '-',
                'event_type' => $event->event_type,
                'mq2_value' => $event->mq2_value,
                'status' => $event->status,
            ]),
        ]);
    }
}
