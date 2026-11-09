<?php

namespace App\Models;

use Database\Factories\AlarmEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlarmEvent extends Model
{
    /** @use HasFactory<AlarmEventFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'device_id',
        'event_type',
        'mq2_value',
        'status',
        'detected_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mq2_value' => 'integer',
            'detected_at' => 'datetime',
        ];
    }

    /**
     * Get the device that triggered the alarm event.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
