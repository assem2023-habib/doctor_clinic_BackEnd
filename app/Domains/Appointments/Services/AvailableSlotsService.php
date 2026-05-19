<?php

namespace App\Domains\Appointments\Services;

use App\Domains\Appointments\Models\Appointment;
use App\Domains\Doctors\Models\Doctor;
use App\Enums\AppointmentStatusEnum;
use App\Enums\DayOfWeekEnum;
use Carbon\Carbon;

class AvailableSlotsService
{
    public function getAvailableSlots(Doctor $doctor, string $date, int $slotDurationMinutes = 120): array
    {
        $dayOfWeek = Carbon::parse($date)->format('l');
        $dayOfWeekEnum = DayOfWeekEnum::from(strtolower($dayOfWeek));

        $schedules = $doctor->schedules()
            ->where('day_of_week', $dayOfWeekEnum)
            ->where('is_active', true)
            ->get();

        if ($schedules->isEmpty()) {
            return [];
        }

        $existingAppointments = Appointment::where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', $date)
            ->whereIn('status', [
                AppointmentStatusEnum::Set,
                AppointmentStatusEnum::Accepted,
                AppointmentStatusEnum::Pending,
                AppointmentStatusEnum::Confirmed,
            ])
            ->get(['start_time', 'end_time']);

        $slots = [];

        foreach ($schedules as $schedule) {
            $start = Carbon::parse($schedule->start_time->format('H:i'));
            $end = Carbon::parse($schedule->end_time->format('H:i'));

            while ($start->copy()->addMinutes($slotDurationMinutes)->lte($end)) {
                $slotStart = $start->format('H:i');
                $slotEnd = $start->copy()->addMinutes($slotDurationMinutes)->format('H:i');

                if (!$this->isSlotOverlapping($slotStart, $slotEnd, $existingAppointments)) {
                    $slots[] = [
                        'start_time' => $slotStart,
                        'end_time' => $slotEnd,
                    ];
                }

                $start->addMinutes($slotDurationMinutes);
            }
        }

        return $slots;
    }

    private function isSlotOverlapping(string $startTime, string $endTime, iterable $appointments): bool
    {
        foreach ($appointments as $appointment) {
            $existingStart = Carbon::parse($appointment->start_time instanceof \DateTime
                ? $appointment->start_time->format('H:i')
                : $appointment->start_time);
            $existingEnd = Carbon::parse($appointment->end_time instanceof \DateTime
                ? $appointment->end_time->format('H:i')
                : $appointment->end_time);

            if ($existingStart->format('H:i') < $endTime && $existingEnd->format('H:i') > $startTime) {
                return true;
            }
        }

        return false;
    }
}
