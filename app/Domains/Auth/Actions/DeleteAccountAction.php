<?php

namespace App\Domains\Auth\Actions;

use App\Domains\Appointments\Models\Appointment;
use App\Domains\Appointments\Models\AppointmentStatusLog;
use App\Domains\Doctors\Services\DoctorDeletionService;
use App\Domains\Patients\Services\PatientDeletionService;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteAccountAction
{
    public function __construct(
        private readonly DoctorDeletionService $doctorDeletionService,
        private readonly PatientDeletionService $patientDeletionService,
    ) {}

    public function execute(User $user): void
    {
        if ($user->hasRole('doctor') && $user->doctor) {
            $this->doctorDeletionService->deleteDoctor($user->doctor, $user);
            return;
        }

        if ($user->hasRole('patient') && $user->patient) {
            $this->patientDeletionService->deletePatient($user->patient, $user);
            return;
        }

        DB::transaction(function () use ($user) {
            $userLabel = $user->id . ': ' . $user->first_name . ' ' . $user->last_name;

            AppointmentStatusLog::where('changed_by', 'like', $user->id . ':%')->delete();

            Appointment::where('created_by', 'like', $user->id . ':%')->delete();

            $user->tokens()->delete();

            $user->delete();
        });
    }
}
