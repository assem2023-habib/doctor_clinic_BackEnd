<?php

namespace App\Domains\Supervisions\Models;

use App\Domains\Doctors\Models\Doctor;
use App\Domains\Patients\Models\Patient;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class DoctorPatient extends Pivot
{
    use HasUuidV7;

    protected $table = 'doctor_patient';

    protected $fillable = ['doctor_id', 'patient_id', 'assigned_by', 'notes', 'supervision_status', 'supervision_start', 'supervision_end'];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
