<?php

namespace App\Models;

use Database\Factories\DeviceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Device extends Model
{
    /** @use HasFactory<DeviceFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'device_code',
        'name',
        'is_online',
        'last_heartbeat',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_online' => 'boolean',
            'last_heartbeat' => 'datetime',
        ];
    }

    /**
     * Get the sensor readings recorded by the device.
     */
    public function sensorReadings(): HasMany
    {
        return $this->hasMany(SensorReading::class);
    }

    /**
     * Get the latest sensor reading recorded by the device.
     */
    public function latestSensorReading(): HasOne
    {
        return $this->hasOne(SensorReading::class)->latestOfMany();
    }

    /**
     * Get the alarm events triggered by the device.
     */
    public function alarmEvents(): HasMany
    {
        return $this->hasMany(AlarmEvent::class);
    }
}
