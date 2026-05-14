<?php

namespace App\Domains\Auth\Actions;

use App\Domains\Auth\DTOs\UpdateProfileData;
use App\Domains\Images\Actions\UploadImageAction;
use App\Domains\Images\DTOs\UploadImageData;
use App\Enums\ImageTypeEnum;
use App\Models\User;

class UpdateProfileAction
{
    public function __construct(
        private readonly UploadImageAction $uploadImageAction,
    ) {}

    public function execute(User $user, UpdateProfileData $data): User
    {
        $user->update($data->toUpdateArray());

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
