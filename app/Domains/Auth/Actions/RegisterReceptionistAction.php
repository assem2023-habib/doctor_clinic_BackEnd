<?php

namespace App\Domains\Auth\Actions;

use App\Domains\Auth\DTOs\RegisterReceptionistData;
use App\Enums\RoleEnum;
use App\Models\User;

class RegisterReceptionistAction
{
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
            'role' => RoleEnum::Receptionist,
            'is_active' => true,
            'password' => bcrypt($data->password),
        ]);

        $user->receptionist()->create([
            'shift_start' => $data->shiftStart,
            'shift_end' => $data->shiftEnd,
        ]);

        return $user;
    }
}
