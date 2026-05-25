<?php

namespace App\Domains\Appointments\Services;

use App\Domains\Appointments\Models\Appointment;
use App\Domains\Doctors\Models\Doctor;
use App\Domains\Notifications\Services\FirebaseRtdbService;
use App\Enums\AppointmentStatusEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AppointmentRtdbService
{
    private const array BOOKED_STATUSES = [
        AppointmentStatusEnum::Set,
        AppointmentStatusEnum::Accepted,
        AppointmentStatusEnum::InProgress,
        AppointmentStatusEnum::Confirmed,
    ];

    public function __construct(
        private readonly FirebaseRtdbService $rtdb,
    ) {}

    public function syncAppointment(Appointment $appointment): void
    {
        if (!$this->rtdb->isAvailable()) {
            return;
        }

        $doctorId = $appointment->doctor_id;
        $date = $appointment->appointment_date?->format('Y-m-d');
        $path = "doctors/{$doctorId}/booked-appointments/{$date}/{$appointment->id}";

        $data = $this->buildAppointmentData($appointment);

        $this->rtdb->setValue($path, $data);
        $this->syncDoctorName($appointment);
    }

    public function removeAppointment(Appointment $appointment): void
    {
        if (!$this->rtdb->isAvailable()) {
            return;
        }

        $doctorId = $appointment->doctor_id;
        $date = $appointment->appointment_date?->format('Y-m-d');
        $path = "doctors/{$doctorId}/booked-appointments/{$date}/{$appointment->id}";

        $this->rtdb->removeValue($path);
    }

    public function syncIfBooked(Appointment $appointment): void
    {
        if (in_array($appointment->status, self::BOOKED_STATUSES, true)) {
            $this->syncAppointment($appointment);
        } else {
            $this->removeAppointment($appointment);
        }
    }

    private function syncDoctorName(Appointment $appointment): void
    {
        $doctor = $appointment->doctor;
        $doctorUser = $doctor?->user;
        $name = $doctorUser ? trim($doctorUser->first_name . ' ' . $doctorUser->last_name) : null;

        if ($name) {
            $path = "doctors/{$appointment->doctor_id}/doctor_name";
            $this->rtdb->setValue($path, $name);
        }
    }

    public function removeExpiredAppointments(): int
    {
        if (!$this->rtdb->isAvailable()) {
            return 0;
        }

        $removed = 0;

        $statuses = array_map(fn ($s) => $s->value, self::BOOKED_STATUSES);

        $expiredAppointments = Appointment::whereIn('status', $statuses)
            ->where(function ($q) {
                $q->whereDate('appointment_date', '<', Carbon::today())
                    ->orWhere(function ($q2) {
                        $q2->whereDate('appointment_date', '=', Carbon::today())
                            ->whereTime('end_time', '<=', Carbon::now()->format('H:i:s'));
                    });
            })
            ->get();

        foreach ($expiredAppointments as $appointment) {
            try {
                $this->removeAppointment($appointment);
                $removed++;
            } catch (\Exception $e) {
                Log::error('Failed to remove expired appointment from RTDB', [
                    'appointment_id' => $appointment->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $removed;
    }

    public function syncDoctorAppointments(Doctor $doctor): int
    {
        if (!$this->rtdb->isAvailable()) {
            return 0;
        }

        $synced = 0;
        $statuses = array_map(fn ($s) => $s->value, self::BOOKED_STATUSES);

        $appointments = Appointment::where('doctor_id', $doctor->id)
            ->whereIn('status', $statuses)
            ->get();

        foreach ($appointments as $appointment) {
            try {
                $this->syncAppointment($appointment);
                $synced++;
            } catch (\Exception $e) {
                Log::error('Failed to sync doctor appointment to RTDB', [
                    'appointment_id' => $appointment->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $synced;
    }

    private function buildAppointmentData(Appointment $appointment): array
    {
        $patient = $appointment->patient;
        $patientUser = $patient?->user;

        return [
            'id' => $appointment->id,
            'doctor_id' => $appointment->doctor_id,
            'patient_id' => $appointment->patient_id,
            'patient_name' => $patientUser ? trim($patientUser->first_name . ' ' . $patientUser->last_name) : null,
            'patient_phone' => $patientUser?->phone,
            'appointment_date' => $appointment->appointment_date?->format('Y-m-d'),
            'start_time' => $appointment->start_time?->format('H:i'),
            'end_time' => $appointment->end_time?->format('H:i'),
            'status' => $appointment->status->value,
            'reason' => $appointment->reason,
            'notes' => $appointment->notes,
            'synced_at' => Carbon::now()->toIso8601String(),
            'synced_at_timestamp' => ['.sv' => 'timestamp'],
        ];
    }
}
