<?php

namespace App\Domains\Doctors\Requests;

use App\Enums\GenderEnum;
use App\Enums\ImageTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreDoctorRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:1000'],
            'gender' => ['required', Rule::enum(GenderEnum::class)],
            'birthday_date' => ['nullable', 'date'],
            'specialization_id' => ['required', 'string', 'exists:specializations,id'],
            'experience_months' => ['required', 'integer', 'min:0', 'max:1200'],
            'password' => ['required', Password::defaults()],
            'file' => ['nullable', 'image', 'max:' . ImageTypeEnum::User->maxSize(), 'mimes:jpg,jpeg,png,webp'],
        ];
    }
}
