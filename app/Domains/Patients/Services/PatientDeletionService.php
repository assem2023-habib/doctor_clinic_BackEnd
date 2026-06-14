<?php

namespace App\Domains\Patients\Services;

use App\Domains\Appointments\Models\Appointment;
use App\Domains\Patients\Models\Patient;
use App\Domains\Shared\Exceptions\ApiServiceException;
use App\Enums\AppointmentStatusEnum;
use App\Enums\HttpStatusEnum;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PatientDeletionService
{
    public function deletePatient(Patient $patient, User $actingUser): void
    {
        $user = $patient->user;

        $activeStatuses = [
            AppointmentStatusEnum::Confirmed,
            AppointmentStatusEnum::Completed,
        ];

        $activeCount = Appointment::where('patient_id', $patient->id)
            ->whereIn('status', $activeStatuses)
            ->count();

        if ($activeCount > 0) {
            throw new ApiServiceException(
                errorCode: 'PATIENT_HAS_ACTIVE_APPOINTMENTS',
                message: __('Patient has active appointments. Cannot delete.'),
                status: HttpStatusEnum::Conflict,
            );
        }

        DB::transaction(function () use ($user) {
            if ($user->image) {
                Storage::disk('local')->delete($user->image->getRawOriginal('url'));
                $user->image->delete();
            }

            $user->delete();
        });
    }
}
