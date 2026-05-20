<?php

namespace App\Domains\Appointments\Actions;

use App\Domains\Appointments\Models\Appointment;
use App\Domains\Appointments\Models\AppointmentStatusLog;
use App\Enums\AppointmentStatusEnum;

class StartAppointmentAction
{
    public function execute(Appointment $appointment, string $changedBy): Appointment
    {
        $oldStatus = $appointment->status;

        $appointment->update(['status' => AppointmentStatusEnum::InProgress]);

        AppointmentStatusLog::create([
            'appointment_id' => $appointment->id,
            'old_status' => $oldStatus,
            'new_status' => AppointmentStatusEnum::InProgress,
            'changed_by' => $changedBy,
            'created_at' => now(),
        ]);

        return $appointment->fresh();
    }
}
