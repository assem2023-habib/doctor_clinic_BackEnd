<?php

namespace App\Domains\Appointments\Models;

use App\Enums\AppointmentStatusEnum;
use App\Models\User;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentStatusLog extends Model
{
    use HasUuidV7;

    public $timestamps = false;

    protected $fillable = [
        'appointment_id',
        'old_status',
        'new_status',
        'changed_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_status' => AppointmentStatusEnum::class,
            'new_status' => AppointmentStatusEnum::class,
            'created_at' => 'datetime',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
