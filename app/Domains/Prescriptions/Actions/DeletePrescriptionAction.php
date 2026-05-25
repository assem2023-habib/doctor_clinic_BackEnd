<?php

namespace App\Domains\Prescriptions\Actions;

use App\Domains\Prescriptions\Enums\PrescriptionStatusEnum;
use App\Domains\Prescriptions\Models\Prescription;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class DeletePrescriptionAction
{
    public function execute(Prescription $prescription): void
    {
        if (in_array($prescription->status, [PrescriptionStatusEnum::Archived, PrescriptionStatusEnum::Expired], true)) {
            throw new HttpResponseException(response()->json([
                'status' => 422,
                'message' => __('Cannot delete an archived or expired prescription'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        if ($prescription->created_at->diffInDays(now()) >= 2) {
            throw new HttpResponseException(response()->json([
                'status' => 422,
                'message' => __('Cannot delete a prescription older than 2 days'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        $prescription->delete();
    }
}
