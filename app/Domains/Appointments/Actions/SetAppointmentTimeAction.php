<?php

namespace App\Domains\Appointments\Actions;

use App\Domains\Appointments\DTOs\SetAppointmentTimeData;
use App\Domains\Appointments\Models\Appointment;
use App\Domains\Appointments\Models\AppointmentStatusLog;
use App\Enums\AppointmentStatusEnum;

class SetAppointmentTimeAction
{
    public function execute(Appointment $appointment, SetAppointmentTimeData $data): Appointment
    {
        $oldStatus = $appointment->status;

        $appointment->update([
            'appointment_date' => $data->appointmentDate,
            'start_time' => $data->startTime,
            'end_time' => $data->endTime,
            'status' => AppointmentStatusEnum::Set,
        ]);

        AppointmentStatusLog::create([
            'appointment_id' => $appointment->id,
            'old_status' => $oldStatus,
            'new_status' => AppointmentStatusEnum::Set,
            'changed_by' => $data->changedBy,
            'created_at' => now(),
        ]);

        return $appointment->fresh();
    }
}
