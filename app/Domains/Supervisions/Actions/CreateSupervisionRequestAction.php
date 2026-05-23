<?php

namespace App\Domains\Supervisions\Actions;

use App\Domains\Doctors\Models\Doctor;
use App\Domains\Notifications\DTOs\NotificationData;
use App\Domains\Notifications\Services\NotificationManager;
use App\Domains\Patients\Models\Patient;
use App\Domains\Supervisions\Models\SupervisionRequest;

class CreateSupervisionRequestAction
{
    public function __construct(
        private readonly NotificationManager $notificationManager,
    ) {}

    public function execute(Patient $patient, Doctor $doctor, ?string $notes = null): SupervisionRequest
    {
        $patient->loadMissing('user');

        $hasSameSpecialization = $patient->doctors()
            ->wherePivot('supervision_status', 'active')
            ->where('specialization', $doctor->specialization->value)
            ->exists();

        if ($hasSameSpecialization) {
            abort(409, __('Patient already has an active supervision with a doctor of this specialization'));
        }

        $exists = SupervisionRequest::where('patient_id', $patient->id)
            ->where('doctor_id', $doctor->id)
            ->where('status', 'pending')
            ->exists();

        if ($exists) {
            abort(409, __('A pending request to this doctor already exists'));
        }

        $maxPendingRequests = 5;

        $pendingCount = SupervisionRequest::where('patient_id', $patient->id)
            ->where('status', 'pending')
            ->count();

        if ($pendingCount >= $maxPendingRequests) {
            abort(409, __('Maximum of :max pending supervision requests reached', ['max' => $maxPendingRequests]));
        }

        $request = SupervisionRequest::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => 'pending',
            'notes' => $notes,
        ]);

        $doctor->loadMissing('user');

        $this->notificationManager->send('supervision.requested', new NotificationData(
            topic: 'supervision.requested',
            title: __('New supervision request'),
            body: [
                'patient_name' => $patient->user->first_name . ' ' . $patient->user->last_name,
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'supervision_request_id' => $request->id,
            ],
            userIds: [$doctor->user_id],
        ));

        return $request;
    }
}
