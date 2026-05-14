<?php

namespace App\Domains\Auth\DTOs;

use App\Domains\Auth\Requests\UpdateProfileRequest;
use App\Enums\GenderEnum;
use Illuminate\Http\UploadedFile;

class UpdateProfileData
{
    private array $fields = [];

    public ?UploadedFile $file = null;

    private function __construct() {}

    public static function fromRequest(UpdateProfileRequest $request): self
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

    public function toUpdateArray(): array
    {
        return $this->fields;
    }

    public function hasFile(): bool
    {
        return $this->file !== null;
    }
}
