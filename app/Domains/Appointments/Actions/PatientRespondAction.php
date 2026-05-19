<?php

namespace App\Domains\Appointments\Actions;

use App\Domains\Appointments\Enums\PatientResponseEnum;
use App\Domains\Appointments\Models\Appointment;
use App\Domains\Appointments\Models\AppointmentStatusLog;
use App\Enums\AppointmentStatusEnum;

class PatientRespondAction
{
    public function execute(Appointment $appointment, PatientResponseEnum $response, string $changedBy): Appointment
    {
        $oldStatus = $appointment->status;

        $newStatus = match ($response) {
            PatientResponseEnum::Accepted => AppointmentStatusEnum::Accepted,
            PatientResponseEnum::Rejected => AppointmentStatusEnum::Rejected,
        };

        $appointment->update(['status' => $newStatus]);

        AppointmentStatusLog::create([
            'appointment_id' => $appointment->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $changedBy,
            'created_at' => now(),
        ]);

        return $appointment->fresh();
    }
}
