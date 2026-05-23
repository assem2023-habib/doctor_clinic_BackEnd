<?php

namespace App\Domains\Receptionists\DTOs;

use App\Domains\Receptionists\Requests\PatchReceptionistRequest;
use App\Domains\Receptionists\Requests\UpdateReceptionistRequest;
use App\Enums\GenderEnum;
use Illuminate\Http\UploadedFile;

class UpdateReceptionistData
{
    private array $userFields = [];

    private array $receptionistFields = [];

    public ?UploadedFile $file = null;

    private function __construct() {}

    public static function fromRequest(UpdateReceptionistRequest $request): self
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
        $dto->receptionistFields = [
            'shift_start' => $request->shift_start,
            'shift_end' => $request->shift_end,
        ];
        $dto->file = $request->file('file');
        return $dto;
    }

    public static function fromRequestPartial(PatchReceptionistRequest $request): self
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

        if ($request->exists('shift_start')) {
            $dto->receptionistFields['shift_start'] = $request->shift_start;
        }

        if ($request->exists('shift_end')) {
            $dto->receptionistFields['shift_end'] = $request->shift_end;
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

    public function getReceptionistFields(): array
    {
        return $this->receptionistFields;
    }

    public function hasFile(): bool
    {
        return $this->file !== null;
    }
}
