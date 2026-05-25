<?php

namespace App\Domains\MedicalRecords\Models;

use App\Domains\Doctors\Models\Doctor;
use App\Domains\Patients\Models\Patient;
use App\Models\User;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalRecordTransfer extends Model
{
    use HasUuidV7;

    protected $fillable = [
        'medical_record_id',
        'from_doctor_id',
        'to_doctor_id',
        'patient_id',
        'transferred_by',
        'initiated_by_role',
        'reason',
    ];

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function fromDoctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'from_doctor_id');
    }

    public function toDoctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'to_doctor_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function transferredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }
}
