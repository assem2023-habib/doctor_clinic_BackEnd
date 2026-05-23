<?php

namespace App\Domains\Supervisions\Actions;

use App\Domains\Notifications\DTOs\NotificationData;
use App\Domains\Notifications\Services\NotificationManager;
use App\Domains\Supervisions\Enums\SupervisionRequestStatusEnum;
use App\Domains\Supervisions\Models\SupervisionRequest;

class CancelSupervisionRequestAction
{
    public function __construct(
        private readonly NotificationManager $notificationManager,
    ) {}

    public function execute(SupervisionRequest $request): void
    {
        if ($request->status !== SupervisionRequestStatusEnum::Pending) {
            abort(422, __('Supervision request is not pending'));
        }

        $request->loadMissing(['doctor.user']);

        $request->update([
            'status' => SupervisionRequestStatusEnum::Cancelled,
            'responded_at' => now(),
        ]);

        $this->notificationManager->send('supervision.cancelled', new NotificationData(
            topic: 'supervision.cancelled',
            title: __('Supervision request cancelled'),
            body: [
                'supervision_request_id' => $request->id,
            ],
            userIds: [$request->doctor->user_id],
        ));
    }
}
