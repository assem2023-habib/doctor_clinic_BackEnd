<?php

namespace App\Domains\Appointments\Actions;

use App\Domains\Appointments\DTOs\RequestAppointmentData;
use App\Domains\Appointments\Models\Appointment;
use App\Enums\AppointmentStatusEnum;

class RequestAppointmentAction
{
    public function execute(RequestAppointmentData $data): Appointment
    {
        $appointment = Appointment::create([
            'doctor_id' => $data->doctorId,
            'patient_id' => $data->patientId,
            'status' => AppointmentStatusEnum::Requested,
            'reason' => $data->reason,
            'notes' => $data->preferredDate ? "Preferred date: {$data->preferredDate}" : null,
            'created_by' => $data->createdBy,
        ]);

        return $appointment;
    }
}
