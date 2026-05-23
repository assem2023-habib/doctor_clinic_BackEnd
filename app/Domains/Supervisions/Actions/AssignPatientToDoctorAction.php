<?php

namespace App\Domains\Supervisions\Actions;

use App\Domains\Doctors\Models\Doctor;
use App\Domains\Patients\Models\Patient;
use App\Models\User;
use Illuminate\Support\Carbon;

class AssignPatientToDoctorAction
{
    public function execute(Doctor $doctor, Patient $patient, User $assigner, ?string $notes = null, ?string $supervisionStatus = 'active', ?Carbon $supervisionStart = null, ?Carbon $supervisionEnd = null): void
    {
        $hasSameSpecialization = Doctor::whereHas('patients', fn($q) => $q->where('patient_id', $patient->id))
            ->where('id', '!=', $doctor->id)
            ->where('specialization', $doctor->specialization)
            ->exists();

        if ($hasSameSpecialization) {
            abort(409, __('Patient already has a doctor with specialization :specialization', [
                'specialization' => $doctor->specialization->value ?? $doctor->specialization,
            ]));
        }

        $assignedBy = "{$assigner->id}: {$assigner->first_name} {$assigner->last_name}";

        $doctor->patients()->syncWithoutDetaching([
            $patient->id => [
                'assigned_by' => $assignedBy,
                'notes' => $notes,
                'supervision_status' => $supervisionStatus,
                'supervision_start' => $supervisionStart,
                'supervision_end' => $supervisionEnd,
            ],
        ]);
    }
}
