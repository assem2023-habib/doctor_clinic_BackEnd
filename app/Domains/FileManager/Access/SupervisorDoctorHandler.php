<?php

namespace App\Domains\FileManager\Access;

use App\Domains\FileManager\Models\File;
use App\Models\User;

class SupervisorDoctorHandler extends AbstractFileAccessHandler
{
    public function handle(User $user, File $file): ?bool
    {
        $doctor = $user->doctor;

        if ($doctor === null) {
            return $this->next($user, $file);
        }

        $patientUserId = $file->medicalRecord->patient->user_id;

        $isSupervisor = $doctor->patients()
            ->where('user_id', $patientUserId)
            ->wherePivot('supervision_status', 'approved')
            ->exists();

        if ($isSupervisor) {
            return true;
        }

        return $this->next($user, $file);
    }
}
