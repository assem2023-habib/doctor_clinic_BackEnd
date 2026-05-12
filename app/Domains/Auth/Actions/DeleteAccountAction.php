<?php

namespace App\Domains\Auth\Actions;

use App\Domains\Appointments\Models\Appointment;
use App\Domains\Appointments\Models\AppointmentStatusLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteAccountAction
{
    public function execute(User $user): void
    {
        DB::transaction(function () use ($user) {
            AppointmentStatusLog::where('changed_by', $user->id)->delete();

            Appointment::where('created_by', $user->id)->delete();

            $user->tokens()->delete();

            $user->delete();
        });
    }
}
