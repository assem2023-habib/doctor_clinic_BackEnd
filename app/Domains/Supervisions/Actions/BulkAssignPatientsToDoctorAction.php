<?php

namespace App\Domains\Supervisions\Actions;

use App\Domains\Doctors\Models\Doctor;
use App\Domains\Patients\Models\Patient;
use App\Models\User;

class BulkAssignPatientsToDoctorAction
{
    public function __construct(
        private readonly AssignPatientToDoctorAction $assignAction,
    ) {}

    public function execute(Doctor $doctor, array $patientIds, User $assigner, ?string $notes = null): array
    {
        $assigned = [];
        $skipped = [];
        $errors = [];

        foreach ($patientIds as $patientId) {
            $patient = Patient::find($patientId);

            if (!$patient) {
                $errors[] = ['patient_id' => $patientId, 'reason' => 'Patient not found'];
                continue;
            }

            try {
                $this->assignAction->execute($doctor, $patient, $assigner, $notes);
                $assigned[] = $patientId;
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                $skipped[] = [
                    'patient_id' => $patientId,
                    'reason' => $e->getMessage(),
                ];
            }
        }

        return [
            'assigned' => $assigned,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }
}
