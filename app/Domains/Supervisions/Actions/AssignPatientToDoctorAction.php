<?php

namespace App\Domains\Supervisions\Actions;

use App\Domains\Doctors\Models\Doctor;
use App\Domains\Patients\Models\Patient;
use App\Models\User;

class AssignPatientToDoctorAction
{
    public function execute(Doctor $doctor, Patient $patient, User $assigner, ?string $notes = null): void
    {
        $assignedBy = "{$assigner->id}: {$assigner->first_name} {$assigner->last_name}";

        $doctor->patients()->syncWithoutDetaching([
            $patient->id => [
                'assigned_by' => $assignedBy,
                'notes' => $notes,
            ],
        ]);
    }
}
