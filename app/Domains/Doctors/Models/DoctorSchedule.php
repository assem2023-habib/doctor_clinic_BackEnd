<?php

namespace App\Domains\Doctors\Models;

use App\Enums\DayOfWeekEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorSchedule extends Model
{
    use HasUuidV7;

    protected $fillable = [
        'doctor_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => DayOfWeekEnum::class,
            'is_active' => 'boolean',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
