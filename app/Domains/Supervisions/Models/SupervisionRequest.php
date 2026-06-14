<?php

namespace App\Domains\Supervisions\Models;

use App\Domains\Doctors\Models\Doctor;
use App\Domains\Patients\Models\Patient;
use App\Domains\Supervisions\Enums\SupervisionRequestStatusEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupervisionRequest extends Model
{
    use HasUuidV7;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'status',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SupervisionRequestStatusEnum::class,
            'responded_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
