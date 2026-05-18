<?php

namespace App\Domains\Supervisions\Actions;

use App\Domains\Doctors\Models\Doctor;
use App\Domains\Patients\Models\Patient;

class RemovePatientFromDoctorAction
{
    public function execute(Doctor $doctor, Patient $patient): void
    {
        $doctor->patients()->detach($patient->id);
    }
}
