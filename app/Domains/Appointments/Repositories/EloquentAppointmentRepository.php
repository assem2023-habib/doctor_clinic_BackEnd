<?php

namespace App\Domains\Appointments\Repositories;

use App\Domains\Appointments\Models\Appointment;
use App\Enums\AppointmentStatusEnum;

class EloquentAppointmentRepository implements AppointmentRepositoryInterface
{
    public function hasOverlap(
        string $doctorId,
        string $date,
        string $startTime,
        string $endTime,
        ?string $excludeAppointmentId = null,
    ): bool {
        $query = Appointment::where('doctor_id', $doctorId)
            ->where('appointment_date', $date)
            ->whereNotIn('status', [AppointmentStatusEnum::Cancelled->value])
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime);

        if ($excludeAppointmentId) {
            $query->where('id', '!=', $excludeAppointmentId);
        }

        return $query->exists();
    }
}
