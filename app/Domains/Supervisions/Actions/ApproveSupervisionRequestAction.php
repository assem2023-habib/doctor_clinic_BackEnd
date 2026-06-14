<?php

namespace App\Domains\Supervisions\Actions;

use App\Domains\MedicalRecords\Actions\TransferMedicalRecordAction;
use App\Domains\MedicalRecords\Models\MedicalRecord;
use App\Domains\Notifications\DTOs\NotificationData;
use App\Domains\Notifications\Services\NotificationManager;
use App\Domains\Shared\Exceptions\ApiServiceException;
use App\Domains\Supervisions\Enums\SupervisionRequestStatusEnum;
use App\Domains\Supervisions\Models\SupervisionRequest;
use App\Enums\HttpStatusEnum;
use App\Models\User;

class ApproveSupervisionRequestAction
{
    public function __construct(
        private readonly AssignPatientToDoctorAction $assignAction,
        private readonly TransferMedicalRecordAction $transferAction,
        private readonly NotificationManager $notificationManager,
    ) {}

    public function execute(SupervisionRequest $request, User $approver): void
    {
        if ($request->status !== SupervisionRequestStatusEnum::Pending) {
            throw new ApiServiceException(
                errorCode: 'SUPERVISION_REQUEST_NOT_PENDING',
                message: __('Supervision request is not pending'),
                status: HttpStatusEnum::UnprocessableEntity,
            );
        }

        $request->loadMissing(['patient.user', 'doctor.user']);

        $this->assignAction->execute(
            doctor: $request->doctor,
            patient: $request->patient,
            assigner: $approver,
            supervisionStatus: 'active',
            supervisionStart: now(),
        );

        $request->update([
            'status' => SupervisionRequestStatusEnum::Approved,
            'responded_at' => now(),
        ]);

        SupervisionRequest::where('patient_id', $request->patient_id)
            ->where('id', '!=', $request->id)
            ->where('status', SupervisionRequestStatusEnum::Pending)
            ->whereHas('doctor', fn ($q) => $q->where('specialization_id', $request->doctor->specialization_id))
            ->update(['status' => SupervisionRequestStatusEnum::Cancelled]);

        $oldRecord = MedicalRecord::where('patient_id', $request->patient_id)
            ->where('doctor_id', '!=', $request->doctor->id)
            ->whereHas('doctor', fn ($q) => $q->where('specialization_id', $request->doctor->specialization_id))
            ->first();

        if ($oldRecord) {
            $this->transferAction->execute(
                medicalRecord: $oldRecord,
                toDoctor: $request->doctor,
                transferredBy: $approver,
                role: 'doctor',
            );
        }

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
