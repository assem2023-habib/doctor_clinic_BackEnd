<?php

namespace App\Domains\Auth\Actions;

use App\Domains\Auth\DTOs\RegisterDoctorData;
use App\Domains\Images\Actions\UploadImageAction;
use App\Domains\Images\DTOs\UploadImageData;
use App\Enums\ImageTypeEnum;
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
            'is_active' => false,
            'password' => bcrypt($data->password),
        ]);

        $user->assignRole('doctor');

        $user->doctor()->create([
            'specialization' => $data->specialization,
            'experience_months' => $data->experienceMonths,
        ]);

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
