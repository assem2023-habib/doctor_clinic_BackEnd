<?php

namespace App\Domains\Patients\Actions;

use App\Domains\Patients\Models\Patient;
use App\Domains\Patients\Services\PatientDeletionService;
use App\Models\User;

class DeletePatientAction
{
    public function __construct(
        private readonly PatientDeletionService $patientDeletionService,
    ) {}

    public function execute(Patient $patient, User $admin): void
    {
        $this->patientDeletionService->deletePatient($patient, $admin);
    }
}
