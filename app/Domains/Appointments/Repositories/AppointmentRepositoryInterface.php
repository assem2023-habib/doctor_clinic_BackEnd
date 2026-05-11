<?php

namespace App\Domains\Appointments\Repositories;

interface AppointmentRepositoryInterface
{
    public function hasOverlap(
        string $doctorId,
        string $date,
        string $startTime,
        string $endTime,
        ?string $excludeAppointmentId = null,
    ): bool;
}
