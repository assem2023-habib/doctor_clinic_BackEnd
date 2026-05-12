<?php

namespace App\Domains\Auth\Actions;

use App\Domains\Auth\DTOs\RegisterDoctorData;
use App\Enums\RoleEnum;
use App\Models\User;

class RegisterDoctorAction
{
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

        return $user;
    }
}
