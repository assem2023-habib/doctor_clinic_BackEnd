<?php

namespace App\Domains\Auth\Actions;

use App\Domains\Auth\DTOs\RegisterDoctorData;
use App\Domains\Images\Actions\UploadImageAction;
use App\Domains\Images\DTOs\UploadImageData;
use App\Enums\ImageTypeEnum;
use App\Enums\RoleEnum;
use App\Models\User;

class RegisterDoctorAction
{
    public function __construct(
        private readonly UploadImageAction $uploadImageAction,
    ) {}

    public function execute(RegisterDoctorData $data): User
    {
        $user = User::create([
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'username' => $data->username,
            'email' => $data->email,
            'phone' => $data->phone,
            'address' => $data->address,
            'gender' => $data->gender,
            'birthday_date' => $data->birthdayDate,
            'role' => RoleEnum::Doctor,
            'is_active' => true,
            'password' => bcrypt($data->password),
        ]);

        $user->doctor()->create([]);

        if ($data->file) {
            $this->uploadImageAction->execute(UploadImageData::fromArray([
                'file' => $data->file,
                'type' => ImageTypeEnum::User,
                'imageable_id' => $user->id,
            ]));
        }

        return $user;
    }
}
