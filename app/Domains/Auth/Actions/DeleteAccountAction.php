<?php

namespace App\Domains\Auth\Actions;

use App\Domains\Appointments\Models\Appointment;
use App\Domains\Appointments\Models\AppointmentStatusLog;
use App\Domains\Doctors\Services\DoctorDeletionService;
use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteAccountAction
{
    public function __construct(
        private readonly DoctorDeletionService $doctorDeletionService,
    ) {}

    public function execute(User $user): void
    {
        if ($user->role === RoleEnum::Doctor && $user->doctor) {
            $this->doctorDeletionService->deleteDoctor($user->doctor, $user);
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
