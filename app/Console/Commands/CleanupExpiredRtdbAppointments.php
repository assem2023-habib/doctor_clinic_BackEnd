<?php

namespace App\Console\Commands;

use App\Domains\Appointments\Models\Appointment;
use App\Domains\Appointments\Models\AppointmentStatusLog;
use App\Domains\Appointments\Services\AppointmentRtdbService;
use App\Enums\AppointmentStatusEnum;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanupExpiredRtdbAppointments extends Command
{
    protected $signature = 'appointments:cleanup-rtdb';

    protected $description = 'Auto-cancel/complete expired appointments + remove from RTDB';

    public function handle(AppointmentRtdbService $rtdb): int
    {
        $this->info('Scanning for expired booked appointments...');

        $removed = $rtdb->removeExpiredAppointments();

        $this->info("Removed {$removed} expired appointment(s) from RTDB.");

        $processed = $this->processExpiredAppointments($rtdb);

        $this->info("Processed {$processed} expired appointment(s) (auto-cancel/complete).");

        return Command::SUCCESS;
    }

    private function processExpiredAppointments(AppointmentRtdbService $rtdb): int
    {
        $deadline = Carbon::now()->subHours(24);
        $deadlineDate = $deadline->format('Y-m-d');
        $deadlineTime = $deadline->format('H:i:s');

        $expired = Appointment::whereIn('status', [
            AppointmentStatusEnum::Set,
            AppointmentStatusEnum::Accepted,
            AppointmentStatusEnum::InProgress,
        ])->where(function ($q) use ($deadlineDate, $deadlineTime) {
            $q->whereDate('appointment_date', '<', $deadlineDate)
              ->orWhere(function ($q2) use ($deadlineDate, $deadlineTime) {
                  $q2->whereDate('appointment_date', '=', $deadlineDate)
                      ->whereTime('end_time', '<=', $deadlineTime);
              });
        })->get();

        $processed = 0;

        foreach ($expired as $appointment) {
            try {
                $oldStatus = $appointment->status;

                $newStatus = $oldStatus === AppointmentStatusEnum::InProgress
                    ? AppointmentStatusEnum::Completed
                    : AppointmentStatusEnum::Cancelled;

                $appointment->update(['status' => $newStatus]);

                AppointmentStatusLog::create([
                    'appointment_id' => $appointment->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'changed_by' => 'system: auto-cleanup',
                    'created_at' => now(),
                ]);

                $rtdb->removeAppointment($appointment);

                $processed++;
            } catch (\Exception $e) {
                $this->error("Failed to process appointment {$appointment->id}: {$e->getMessage()}");
            }
        }

        return $processed;
    }
}
