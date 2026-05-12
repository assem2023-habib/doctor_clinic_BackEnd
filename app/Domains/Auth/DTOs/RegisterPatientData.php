<?php

namespace App\Domains\Auth\DTOs;

use App\Domains\Auth\Requests\RegisterPatientRequest;
use App\Enums\GenderEnum;

class RegisterPatientData
{
    private function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $username,
        public readonly string $email,
        public readonly ?string $phone,
        public readonly ?string $address,
        public readonly GenderEnum $gender,
        public readonly ?string $birthdayDate,
        public readonly string $password,
    ) {}

    public static function fromRequest(RegisterPatientRequest $request): self
    {
        return new self(
            firstName: $request->first_name,
            lastName: $request->last_name,
            username: $request->username,
            email: $request->email,
            phone: $request->phone,
            address: $request->address,
            gender: GenderEnum::from($request->gender),
            birthdayDate: $request->birthday_date,
            password: $request->password,
        );
    }
}
