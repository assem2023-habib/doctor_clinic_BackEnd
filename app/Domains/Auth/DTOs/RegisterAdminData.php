<?php

namespace App\Domains\Auth\DTOs;

use App\Enums\GenderEnum;

class RegisterAdminData
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

    public static function fromArray(array $data): self
    {
        return new self(
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            username: $data['username'],
            email: $data['email'],
            phone: $data['phone'] ?? null,
            address: $data['address'] ?? null,
            gender: GenderEnum::from($data['gender']),
            birthdayDate: $data['birthday_date'] ?? null,
            password: $data['password'],
        );
    }
}
