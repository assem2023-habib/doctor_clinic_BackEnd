<?php

namespace App\Domains\Receptionists\Actions;

use App\Domains\Images\Actions\UploadImageAction;
use App\Domains\Images\DTOs\UploadImageData;
use App\Domains\Receptionists\DTOs\UpdateReceptionistData;
use App\Domains\Receptionists\Models\Receptionist;
use App\Enums\ImageTypeEnum;
use App\Models\User;

class UpdateReceptionistAction
{
    public function __construct(
        private readonly UploadImageAction $uploadImageAction,
    ) {}

    public function execute(Receptionist $receptionist, UpdateReceptionistData $data): User
    {
        $user = $receptionist->user;

        $user->update($data->getUserFields());

        if (!empty($data->getReceptionistFields())) {
            $receptionist->update($data->getReceptionistFields());
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
