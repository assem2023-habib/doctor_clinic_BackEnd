<?php

namespace App\Domains\Doctors\DTOs;

use App\Domains\Doctors\Requests\UpdateDoctorRequest;
use App\Enums\GenderEnum;
use Illuminate\Http\UploadedFile;

class UpdateDoctorData
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
        public readonly ?UploadedFile $file,
    ) {}

    public static function fromRequest(UpdateDoctorRequest $request): self
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
            file: $request->file('file'),
        );
    }
}
