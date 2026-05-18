<?php

namespace App\Domains\Doctors\Models;

use App\Domains\Appointments\Models\Appointment;
use App\Domains\Patients\Models\Patient;
use App\Enums\SpecializationEnum;
use App\Models\User;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    use HasUuidV7;

    protected $fillable = ['user_id', 'specialization', 'experience_months'];

    protected function casts(): array
    {
        return [
            'specialization' => SpecializationEnum::class,
            'experience_months' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function patients(): BelongsToMany
    {
        return $this->belongsToMany(Patient::class, 'doctor_patient')
            ->withPivot('assigned_by', 'notes', 'created_at')
            ->withTimestamps();
    }
}
