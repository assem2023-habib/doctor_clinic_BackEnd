<?php

namespace App\Domains\Supervisions\Actions;

use App\Domains\Notifications\DTOs\NotificationData;
use App\Domains\Notifications\Services\NotificationManager;
use App\Domains\Supervisions\Enums\SupervisionRequestStatusEnum;
use App\Domains\Supervisions\Models\SupervisionRequest;

class RejectSupervisionRequestAction
{
    public function __construct(
        private readonly NotificationManager $notificationManager,
    ) {}

    public function execute(SupervisionRequest $request): void
    {
        if ($request->status !== SupervisionRequestStatusEnum::Pending) {
            abort(422, __('Supervision request is not pending'));
        }

        $request->loadMissing(['patient.user', 'doctor.user']);

        $request->update([
            'status' => SupervisionRequestStatusEnum::Rejected,
            'responded_at' => now(),
        ]);

        $this->notificationManager->send('supervision.rejected', new NotificationData(
            topic: 'supervision.rejected',
            title: __('Supervision request rejected'),
            body: [
                'doctor_name' => $request->doctor->user->first_name . ' ' . $request->doctor->user->last_name,
                'doctor_id' => $request->doctor->id,
                'supervision_request_id' => $request->id,
            ],
            userIds: [$request->patient->user_id],
        ));
    }
}
