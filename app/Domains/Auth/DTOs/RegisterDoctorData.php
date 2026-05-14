<?php

namespace App\Domains\Auth\DTOs;

use App\Domains\Auth\Requests\RegisterDoctorRequest;
use App\Enums\GenderEnum;
use App\Enums\SpecializationEnum;
use Illuminate\Http\UploadedFile;

class RegisterDoctorData
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
        public readonly SpecializationEnum $specialization,
        public readonly int $experienceMonths,
        public readonly string $password,
        public readonly ?UploadedFile $file,
    ) {}

    public static function fromRequest(RegisterDoctorRequest $request): self
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
            specialization: SpecializationEnum::from($request->specialization),
            experienceMonths: (int) $request->experience_months,
            password: $request->password,
            file: $request->file('file'),
        );
    }
}
