<?php

namespace App\Domains\Doctors\Services;

use App\Domains\Appointments\Models\Appointment;
use App\Domains\Doctors\Models\Doctor;
use App\Domains\MedicalRecords\Models\MedicalRecord;
use App\Domains\Shared\Exceptions\ApiServiceException;
use App\Enums\AppointmentStatusEnum;
use App\Enums\HttpStatusEnum;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DoctorDeletionService
{
    public function deleteDoctor(Doctor $doctor, User $actingUser): void
    {
        $user = $doctor->user;

        $activeStatuses = [
            AppointmentStatusEnum::Confirmed,
            AppointmentStatusEnum::Completed,
        ];

        $activeCount = Appointment::where('doctor_id', $doctor->id)
            ->whereIn('status', $activeStatuses)
            ->count();

        if ($activeCount > 0) {
            throw new ApiServiceException(
                errorCode: 'DOCTOR_HAS_ACTIVE_APPOINTMENTS',
                message: __('Doctor has active appointments. Cannot delete.'),
                status: HttpStatusEnum::Conflict,
            );
        }

        $actingLabel = $actingUser->id . ': ' . $actingUser->first_name . ' ' . $actingUser->last_name;

        DB::transaction(function () use ($doctor, $user, $actingLabel) {
            Appointment::where('doctor_id', $doctor->id)
                ->whereIn('status', [AppointmentStatusEnum::Confirmed, AppointmentStatusEnum::Completed, AppointmentStatusEnum::Cancelled])
                ->update(['doctor_id' => null]);

            Appointment::where('doctor_id', $doctor->id)
                ->whereIn('status', [AppointmentStatusEnum::Pending, AppointmentStatusEnum::InProgress])
                ->delete();

            Appointment::where('doctor_id', $doctor->id)->update(['doctor_id' => null]);

            Appointment::where('created_by', 'like', $user->id . ':%')
                ->update(['created_by' => $actingLabel]);

            MedicalRecord::where('doctor_id', $doctor->id)
                ->update(['doctor_id' => null]);

            if ($user->image) {
                Storage::disk('local')->delete($user->image->getRawOriginal('url'));
                $user->image->delete();
            }

            $doctor->schedules()->delete();

            $user->delete();
        });
    }
}
