<?php

namespace App\Domains\Appointments\Actions;

use App\Domains\Appointments\DTOs\SuggestAlternativeData;
use App\Domains\Appointments\Models\Appointment;

class SuggestAlternativeAction
{
    public function execute(Appointment $appointment, SuggestAlternativeData $data): Appointment
    {
        $existingNotes = $appointment->notes;
        $newNotes = "Staff suggestion: {$data->message}";

        $appointment->update([
            'notes' => $existingNotes
                ? $existingNotes . "\n\n" . $newNotes
                : $newNotes,
        ]);

        return $appointment->fresh();
    }
}
