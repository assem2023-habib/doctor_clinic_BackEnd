<?php

namespace App\Domains\Appointments\DTOs;

use App\Domains\Appointments\Requests\RequestAppointmentRequest;

class RequestAppointmentData
{
    public function __construct(
        public readonly string $doctorId,
        public readonly string $patientId,
        public readonly ?string $preferredDate,
        public readonly ?string $reason,
        public readonly string $createdBy,
    ) {}

    public static function fromRequest(RequestAppointmentRequest $request, string $patientId, ?string $doctorId = null): self
    {
        return new self(
            doctorId: $doctorId ?? $request->doctor_id,
            patientId: $patientId,
            preferredDate: $request->preferred_date,
            reason: $request->reason,
            createdBy: $request->user()->id,
        );
    }
}
