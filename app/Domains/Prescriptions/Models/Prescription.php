<?php

namespace App\Domains\Prescriptions\Models;

use App\Domains\Doctors\Models\Doctor;
use App\Domains\MedicalRecords\Models\MedicalRecord;
use App\Domains\Patients\Models\Patient;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prescription extends Model
{
    use HasUuidV7;

    protected $fillable = [
        'medical_record_id',
        'doctor_id',
        'patient_id',
        'notes',
    ];

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
