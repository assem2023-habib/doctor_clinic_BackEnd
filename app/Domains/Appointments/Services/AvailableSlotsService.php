<?php

namespace App\Domains\Appointments\Services;

use App\Domains\Appointments\Models\Appointment;
use App\Domains\Doctors\Models\Doctor;
use App\Enums\AppointmentStatusEnum;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AvailableSlotsService
{
    public function getBookedSlots(
        Doctor $doctor,
        int $perPage = 20,
        ?string $date = null,
        ?string $fromDate = null,
        ?string $toDate = null,
    ): LengthAwarePaginator {
        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        $currentTime = $now->format('H:i');

        $query = Appointment::where('doctor_id', $doctor->id)
            ->whereIn('status', [
                AppointmentStatusEnum::Set,
                AppointmentStatusEnum::Accepted,
                AppointmentStatusEnum::InProgress,
                AppointmentStatusEnum::Confirmed,
            ]);

        $query->where(function ($q) use ($today, $currentTime) {
            $q->where('appointment_date', '>', $today)
              ->orWhere(function ($q) use ($today, $currentTime) {
                  $q->where('appointment_date', '=', $today)
                    ->where('start_time', '>', $currentTime);
              });
        });

        if ($date) {
            $query->whereDate('appointment_date', $date);
        } elseif ($fromDate || $toDate) {
            if ($fromDate) {
                $query->whereDate('appointment_date', '>=', $fromDate);
            }
            if ($toDate) {
                $query->whereDate('appointment_date', '<=', $toDate);
            }
        }

        $query->orderBy('appointment_date')->orderBy('start_time');

        return $query->paginate($perPage, ['appointment_date', 'start_time', 'end_time']);
    }
}
