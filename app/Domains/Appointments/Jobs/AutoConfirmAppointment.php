<?php

namespace App\Domains\Appointments\Jobs;

use App\Domains\Appointments\Models\Appointment;
use App\Domains\Appointments\Models\AppointmentStatusLog;
use App\Enums\AppointmentStatusEnum;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AutoConfirmAppointment implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $appointmentId,
    ) {}

    public function handle(): void
    {
        $appointment = Appointment::find($this->appointmentId);

        if (!$appointment || $appointment->status !== AppointmentStatusEnum::Set) {
            return;
        }

        $oldStatus = $appointment->status;

        $appointment->update(['status' => AppointmentStatusEnum::Accepted]);

        AppointmentStatusLog::create([
            'appointment_id' => $appointment->id,
            'old_status' => $oldStatus,
            'new_status' => AppointmentStatusEnum::Accepted,
            'changed_by' => 'system: auto-confirm',
            'created_at' => now(),
        ]);
    }
}
