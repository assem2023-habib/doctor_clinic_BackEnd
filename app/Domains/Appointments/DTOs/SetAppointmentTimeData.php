<?php

namespace App\Domains\Appointments\DTOs;

class SetAppointmentTimeData
{
    public function __construct(
        public readonly string $appointmentDate,
        public readonly string $startTime,
        public readonly string $endTime,
        public readonly string $changedBy,
    ) {}
}
