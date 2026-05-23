<?php

namespace App\Domains\Supervisions\Actions;

use App\Domains\Notifications\DTOs\NotificationData;
use App\Domains\Notifications\Services\NotificationManager;
use App\Domains\Supervisions\Models\SupervisionRequest;
use App\Models\User;

class ApproveSupervisionRequestAction
{
    public function __construct(
        private readonly AssignPatientToDoctorAction $assignAction,
        private readonly NotificationManager $notificationManager,
    ) {}

    public function execute(SupervisionRequest $request, User $approver): void
    {
        if ($request->status !== 'pending') {
            abort(422, __('Supervision request is not pending'));
        }

        $request->loadMissing(['patient.user', 'doctor.user']);

        $this->assignAction->execute(
            doctor: $request->doctor,
            patient: $request->patient,
            assigner: $approver,
            notes: $request->notes,
            supervisionStatus: 'active',
            supervisionStart: now(),
        );

        $request->update([
            'status' => 'approved',
            'responded_at' => now(),
        ]);

        SupervisionRequest::where('patient_id', $request->patient_id)
            ->where('id', '!=', $request->id)
            ->where('status', 'pending')
            ->whereHas('doctor', fn ($q) => $q->where('specialization', $request->doctor->specialization->value))
            ->update(['status' => 'cancelled']);

        $this->notificationManager->send('supervision.approved', new NotificationData(
            topic: 'supervision.approved',
            title: __('Supervision request approved'),
            body: [
                'doctor_name' => $request->doctor->user->first_name . ' ' . $request->doctor->user->last_name,
                'doctor_id' => $request->doctor->id,
                'supervision_request_id' => $request->id,
            ],
            userIds: [$request->patient->user_id],
        ));
    }
}
