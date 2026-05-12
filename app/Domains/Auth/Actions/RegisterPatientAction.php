<?php

namespace App\Domains\Auth\Actions;

use App\Domains\Auth\DTOs\RegisterPatientData;
use App\Domains\Patients\Models\Patient;
use App\Enums\RoleEnum;
use App\Models\User;

class RegisterPatientAction
{
    public function execute(RegisterPatientData $data): User
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
            'role' => RoleEnum::Patient,
            'is_active' => true,
            'password' => bcrypt($data->password),
        ]);

        $user->patient()->create([]);

        return $user;
    }
}
