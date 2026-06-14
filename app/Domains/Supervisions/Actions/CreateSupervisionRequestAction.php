<?php

namespace App\Domains\Supervisions\Actions;

use App\Domains\Doctors\Models\Doctor;
use App\Domains\Notifications\DTOs\NotificationData;
use App\Domains\Notifications\Services\NotificationManager;
use App\Domains\Patients\Models\Patient;
use App\Domains\Shared\Exceptions\ApiServiceException;
use App\Domains\Supervisions\Enums\SupervisionRequestStatusEnum;
use App\Domains\Supervisions\Models\SupervisionRequest;
use App\Enums\HttpStatusEnum;

class CreateSupervisionRequestAction
{
    public function __construct(
        private readonly NotificationManager $notificationManager,
    ) {}

    public function execute(Patient $patient, Doctor $doctor): SupervisionRequest
    {
        $patient->loadMissing('user');

        $hasSameSpecialization = $patient->doctors()
            ->wherePivot('supervision_status', 'active')
            ->where('specialization_id', $doctor->specialization_id)
            ->exists();

        if ($hasSameSpecialization) {
            throw new ApiServiceException(
                errorCode: 'ACTIVE_SUPERVISION_EXISTS',
                message: __('Patient already has an active supervision with a doctor of this specialization'),
                status: HttpStatusEnum::Conflict,
            );
        }

        $recentCancellation = SupervisionRequest::where('patient_id', $patient->id)
            ->where('doctor_id', $doctor->id)
            ->where('status', SupervisionRequestStatusEnum::Cancelled)
            ->where('responded_at', '>=', now()->subDays(7))
            ->exists();

        if ($recentCancellation) {
            throw new ApiServiceException(
                errorCode: 'RECENT_CANCELLATION_COOLDOWN',
                message: __('You cannot request supervision from this doctor again yet. Please wait 7 days after cancellation.'),
                status: HttpStatusEnum::TooManyRequests,
            );
        }

        $exists = SupervisionRequest::where('patient_id', $patient->id)
            ->where('doctor_id', $doctor->id)
            ->where('status', SupervisionRequestStatusEnum::Pending)
            ->exists();

        if ($exists) {
            throw new ApiServiceException(
                errorCode: 'DUPLICATE_SUPERVISION_REQUEST',
                message: __('A pending request to this doctor already exists'),
                status: HttpStatusEnum::Conflict,
            );
        }

        $maxPendingRequests = 5;

        $pendingCount = SupervisionRequest::where('patient_id', $patient->id)
            ->where('status', SupervisionRequestStatusEnum::Pending)
            ->count();

        if ($pendingCount >= $maxPendingRequests) {
            throw new ApiServiceException(
                errorCode: 'MAX_PENDING_SUPERVISION_REQUESTS',
                message: __('Maximum of :max pending supervision requests reached', ['max' => $maxPendingRequests]),
                status: HttpStatusEnum::Conflict,
            );
        }

        $request = SupervisionRequest::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => SupervisionRequestStatusEnum::Pending,
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
