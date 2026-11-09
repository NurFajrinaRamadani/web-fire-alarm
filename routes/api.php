<?php

use App\Http\Controllers\Api\FireAlarmController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Fire Alarm System IoT
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // 1. Endpoint input telemetri sensor dari ESP32
    Route::post('/sensor-data', [FireAlarmController::class, 'store'])->name('api.v1.sensor.store');

    // 2. Endpoint polling status real-time untuk dashboard
    Route::get('/status', [DashboardController::class, 'getStatus'])->name('api.v1.status');

    // 3. Endpoint penyelesaian insiden alarm
    Route::post('/alarm-events/{alarmEvent}/resolve', [FireAlarmController::class, 'resolve'])->name('api.v1.alarm.resolve');
});

// Backward compatibility routes
Route::post('/sensor-data', [FireAlarmController::class, 'store']);
Route::get('/dashboard-status', [DashboardController::class, 'getStatus']);
Route::post('/alarm-events/{alarmEvent}/resolve', [FireAlarmController::class, 'resolve']);
