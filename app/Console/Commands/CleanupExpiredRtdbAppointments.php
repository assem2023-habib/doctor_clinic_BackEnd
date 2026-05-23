<?php

namespace App\Console\Commands;

use App\Domains\Appointments\Services\AppointmentRtdbService;
use Illuminate\Console\Command;

class CleanupExpiredRtdbAppointments extends Command
{
    protected $signature = 'appointments:cleanup-rtdb';

    protected $description = 'Remove expired booked appointments from Firebase Realtime Database';

    public function handle(AppointmentRtdbService $rtdb): int
    {
        $this->info('Scanning for expired booked appointments...');

        $removed = $rtdb->removeExpiredAppointments();

        $this->info("Removed {$removed} expired appointment(s) from RTDB.");

        return Command::SUCCESS;
    }
}
