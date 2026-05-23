<?php

namespace App\Domains\Doctors\Actions;

use App\Domains\Doctors\Models\Doctor;

class ActivateDoctorAccountAction
{
    public function execute(Doctor $doctor): Doctor
    {
        $doctor->user->update(['is_active' => true]);
        $doctor->load('user.roles');

        return $doctor;
    }
}
