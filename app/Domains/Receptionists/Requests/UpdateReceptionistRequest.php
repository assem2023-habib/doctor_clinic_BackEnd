<?php

namespace App\Domains\Receptionists\Requests;

use App\Domains\Receptionists\Models\Receptionist;
use App\Enums\GenderEnum;
use App\Enums\ImageTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReceptionistRequest extends FormRequest
{
    public function rules(): array
    {
        $receptionist = $this->route('receptionist');
        $userId = $receptionist instanceof Receptionist ? $receptionist->user_id : null;

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($userId)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:1000'],
            'gender' => ['required', Rule::enum(GenderEnum::class)],
            'birthday_date' => ['nullable', 'date'],
            'shift_start' => ['nullable', 'date_format:H:i'],
            'shift_end' => ['nullable', 'date_format:H:i'],
            'file' => ['nullable', 'image', 'max:' . ImageTypeEnum::User->maxSize(), 'mimes:jpg,jpeg,png,webp'],
        ];
    }
}
