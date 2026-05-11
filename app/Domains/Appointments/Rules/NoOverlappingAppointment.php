<?php

namespace App\Domains\Appointments\Rules;

use App\Domains\Appointments\Repositories\AppointmentRepositoryInterface;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoOverlappingAppointment implements ValidationRule
{
    public function __construct(
        private string $doctorId,
        private string $date,
        private string $startTime,
        private string $endTime,
        private ?string $excludeAppointmentId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $repository = app(AppointmentRepositoryInterface::class);

        if ($repository->hasOverlap(
            doctorId: $this->doctorId,
            date: $this->date,
            startTime: $this->startTime,
            endTime: $this->endTime,
            excludeAppointmentId: $this->excludeAppointmentId,
        )) {
            $fail(__('validation.no_overlapping_appointment'));
        }
    }
}
