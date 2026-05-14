<?php

namespace App\Domains\Auth\Requests;

use App\Enums\GenderEnum;
use App\Enums\ImageTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'username' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($userId)],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'address' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'gender' => ['sometimes', 'required', Rule::enum(GenderEnum::class)],
            'birthday_date' => ['sometimes', 'nullable', 'date'],
            'file' => ['sometimes', 'nullable', 'image', 'max:' . ImageTypeEnum::User->maxSize(), 'mimes:jpg,jpeg,png,webp'],
        ];
    }
}
