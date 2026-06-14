<?php

namespace App\Domains\Receptionists\Actions;

use App\Domains\Appointments\Models\Appointment;
use App\Domains\Receptionists\Models\Receptionist;
use App\Domains\Shared\Exceptions\ApiServiceException;
use App\Enums\AppointmentStatusEnum;
use App\Enums\HttpStatusEnum;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteReceptionistAction
{
    public function execute(Receptionist $receptionist, User $actingUser): void
    {
        $user = $receptionist->user;

        $activeStatuses = [
            AppointmentStatusEnum::Confirmed,
            AppointmentStatusEnum::Completed,
        ];

        $activeCount = Appointment::where('created_by', $user->id)
            ->whereIn('status', $activeStatuses)
            ->count();

        if ($activeCount > 0) {
            throw new ApiServiceException(
                errorCode: 'RECEPTIONIST_HAS_ACTIVE_APPOINTMENTS',
                message: __('Receptionist has active appointments. Cannot delete.'),
                status: HttpStatusEnum::Conflict,
            );
        }

        $actingLabel = $actingUser->id . ': ' . $actingUser->first_name . ' ' . $actingUser->last_name;

        DB::transaction(function () use ($receptionist, $user, $actingLabel) {
            Appointment::where('created_by', 'like', $user->id . ':%')
                ->update(['created_by' => $actingLabel]);

            if ($user->image) {
                Storage::disk('local')->delete($user->image->getRawOriginal('url'));
                $user->image->delete();
            }

            $user->delete();
        });
    }
}
