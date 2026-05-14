<?php

namespace App\Domains\Doctors\Actions;

use App\Domains\Doctors\DTOs\UpdateDoctorData;
use App\Domains\Doctors\Models\Doctor;
use App\Domains\Images\Actions\UploadImageAction;
use App\Domains\Images\DTOs\UploadImageData;
use App\Enums\ImageTypeEnum;
use App\Models\User;

class UpdateDoctorAction
{
    public function __construct(
        private readonly UploadImageAction $uploadImageAction,
    ) {}

    public function execute(Doctor $doctor, UpdateDoctorData $data): User
    {
        $user = $doctor->user;

        $user->update($data->getUserFields());

        if (!empty($data->getDoctorFields())) {
            $doctor->update($data->getDoctorFields());
        }

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
