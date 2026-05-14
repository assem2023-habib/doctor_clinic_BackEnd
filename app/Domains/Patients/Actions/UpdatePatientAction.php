<?php

namespace App\Domains\Patients\Actions;

use App\Domains\Images\Actions\UploadImageAction;
use App\Domains\Images\DTOs\UploadImageData;
use App\Domains\Patients\DTOs\UpdatePatientData;
use App\Domains\Patients\Models\Patient;
use App\Enums\ImageTypeEnum;
use App\Models\User;

class UpdatePatientAction
{
    public function __construct(
        private readonly UploadImageAction $uploadImageAction,
    ) {}

    public function execute(Patient $patient, UpdatePatientData $data): User
    {
        $user = $patient->user;

        $user->update($data->toArray());

        if ($data->hasFile()) {
            $this->uploadImageAction->execute(UploadImageData::fromArray([
                'file' => $data->file,
                'type' => ImageTypeEnum::User,
                'imageable_id' => $user->id,
            ]));
        }

        return $user->fresh();
    }
}
