<?php

namespace App\Domains\Doctors\DTOs;

use App\Domains\Doctors\Requests\PatchDoctorRequest;
use App\Domains\Doctors\Requests\UpdateDoctorRequest;
use App\Enums\GenderEnum;
use Illuminate\Http\UploadedFile;

class UpdateDoctorData
{
    private array $userFields = [];

    private array $doctorFields = [];

    public ?UploadedFile $file = null;

    private function __construct() {}

    public static function fromRequest(UpdateDoctorRequest $request): self
    {
        $dto = new self;
        $dto->userFields = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'gender' => GenderEnum::from($request->gender)->value,
            'birthday_date' => $request->birthday_date,
        ];
        $dto->doctorFields = [
            'specialization_id' => $request->specialization_id,
            'experience_months' => (int) $request->experience_months,
        ];
        $dto->file = $request->file('file');
        return $dto;
    }

    public static function fromRequestPartial(PatchDoctorRequest $request): self
    {
        $dto = new self;

        foreach (['first_name', 'last_name', 'username', 'email', 'phone', 'address', 'birthday_date'] as $field) {
            if ($request->exists($field)) {
                $dto->userFields[$field] = $request->$field;
            }
        }

        if ($request->exists('gender') && $request->gender !== null) {
            $dto->userFields['gender'] = GenderEnum::from($request->gender)->value;
        }

        if ($request->exists('specialization_id')) {
            $dto->doctorFields['specialization_id'] = $request->specialization_id;
        }

        if ($request->exists('experience_months')) {
            $dto->doctorFields['experience_months'] = (int) $request->experience_months;
        }

        if ($request->exists('file')) {
            $dto->file = $request->file('file');
        }

        return $dto;
    }

    public function getUserFields(): array
    {
        return $this->userFields;
    }

    public function getDoctorFields(): array
    {
        return $this->doctorFields;
    }

    public function hasFile(): bool
    {
        return $this->file !== null;
    }
}
