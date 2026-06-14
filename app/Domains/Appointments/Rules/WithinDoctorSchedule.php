<?php

namespace App\Domains\Appointments\Rules;

use App\Domains\Doctors\Models\Doctor;
use App\Domains\Doctors\Models\DoctorSchedule;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class WithinDoctorSchedule implements ValidationRule
{
    public function __construct(
        private string $doctorId,
        private ?string $date = null,
        private ?string $startTime = null,
        private ?string $endTime = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->date === null) {
            return;
        }

        $doctor = Doctor::where('user_id', $this->doctorId)->first()
            ?? Doctor::find($this->doctorId);

        if ($doctor === null) {
            $fail(__('validation.doctor_not_working_that_day'));
            return;
        }

        $dayName = strtolower(Carbon::parse($this->date)->format('l'));

        $schedules = DoctorSchedule::where('doctor_id', $doctor->id)
            ->where('day_of_week', $dayName)
            ->where('is_active', true)
            ->get();

        if ($schedules->isEmpty()) {
            $fail(__('validation.doctor_not_working_that_day'));
            return;
        }

        if ($this->startTime && $this->endTime) {
            $withinSchedule = $schedules->contains(function ($schedule) {
                return $this->startTime >= $schedule->start_time->format('H:i')
                    && $this->endTime <= $schedule->end_time->format('H:i');
            });

            if (!$withinSchedule) {
                $fail(__('validation.outside_doctor_schedule'));
            }
        }
    }
}
