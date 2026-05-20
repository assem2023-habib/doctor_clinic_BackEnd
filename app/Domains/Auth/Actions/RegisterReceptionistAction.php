<?php

namespace App\Domains\Auth\Actions;

use App\Domains\Auth\DTOs\RegisterReceptionistData;
use App\Domains\Images\Actions\UploadImageAction;
use App\Domains\Images\DTOs\UploadImageData;
use App\Enums\ImageTypeEnum;
use App\Models\User;

class RegisterReceptionistAction
{
    public function __construct(
        private readonly UploadImageAction $uploadImageAction,
    ) {}

    public function execute(RegisterReceptionistData $data): User
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
            'is_active' => true,
            'password' => bcrypt($data->password),
        ]);

        $user->assignRole('receptionist');

        $user->receptionist()->create([
            'shift_start' => $data->shiftStart,
            'shift_end' => $data->shiftEnd,
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
