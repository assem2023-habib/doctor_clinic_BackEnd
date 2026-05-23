<?php

namespace App\Http\Controllers\Api\V1\Receptionist;

use App\Domains\Receptionists\Actions\ActivateReceptionistAccountAction;
use App\Domains\Receptionists\Models\Receptionist;
use App\Domains\Receptionists\Resources\ReceptionistResource;
use App\Domains\Shared\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class ReceptionistController
{
    public function __construct(
        private readonly ActivateReceptionistAccountAction $activateReceptionistAccountAction,
    ) {}

    public function activateAccount(Receptionist $receptionist): JsonResponse
    {
        $receptionist = $this->activateReceptionistAccountAction->execute($receptionist);

        return ApiResponse::success(
            new ReceptionistResource($receptionist->user),
            __('auth.account_activated')
        );
    }
}
