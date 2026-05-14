<?php

namespace App\Domains\Doctors\Actions;

use App\Domains\Doctors\Models\Doctor;
use App\Domains\Doctors\Services\DoctorDeletionService;
use App\Models\User;

class DeleteDoctorAction
{
    public function __construct(
        private readonly DoctorDeletionService $doctorDeletionService,
    ) {}

    public function execute(Doctor $doctor, User $admin): void
    {
        $this->doctorDeletionService->deleteDoctor($doctor, $admin);
    }
}
