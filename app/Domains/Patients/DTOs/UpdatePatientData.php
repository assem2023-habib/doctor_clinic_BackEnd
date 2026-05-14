<?php

namespace App\Domains\Patients\DTOs;

use App\Domains\Patients\Requests\PatchPatientRequest;
use App\Domains\Patients\Requests\UpdatePatientRequest;
use App\Enums\GenderEnum;
use Illuminate\Http\UploadedFile;

class UpdatePatientData
{
    private array $fields = [];

    public ?UploadedFile $file = null;

    private function __construct() {}

    public static function fromRequest(UpdatePatientRequest $request): self
    {
        $dto = new self;
        $dto->fields = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'gender' => GenderEnum::from($request->gender)->value,
            'birthday_date' => $request->birthday_date,
        ];
        $dto->file = $request->file('file');
        return $dto;
    }

    public static function fromRequestPartial(PatchPatientRequest $request): self
    {
        $dto = new self;

        foreach (['first_name', 'last_name', 'username', 'email', 'phone', 'address', 'birthday_date'] as $field) {
            if ($request->exists($field)) {
                $dto->fields[$field] = $request->$field;
            }
        }

        if ($request->exists('gender') && $request->gender !== null) {
            $dto->fields['gender'] = GenderEnum::from($request->gender)->value;
        }

        if ($request->exists('file')) {
            $dto->file = $request->file('file');
        }

        return $dto;
    }

    public function toArray(): array
    {
        return $this->fields;
    }

    public function hasFile(): bool
    {
        return $this->file !== null;
    }
}
