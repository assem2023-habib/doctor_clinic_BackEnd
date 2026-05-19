<?php

namespace App\Domains\Appointments\Actions;

use App\Domains\Appointments\Models\Appointment;
use App\Domains\Appointments\Models\AppointmentStatusLog;
use App\Enums\AppointmentStatusEnum;

class CancelAppointmentAction
{
    public function execute(Appointment $appointment, string $changedBy): Appointment
    {
        $oldStatus = $appointment->status;

        $appointment->update(['status' => AppointmentStatusEnum::Cancelled]);

        AppointmentStatusLog::create([
            'appointment_id' => $appointment->id,
            'old_status' => $oldStatus,
            'new_status' => AppointmentStatusEnum::Cancelled,
            'changed_by' => $changedBy,
            'created_at' => now(),
        ]);

        return $appointment->fresh();
    }
}
