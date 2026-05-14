<?php

namespace App\Domains\Doctors\Requests;

use App\Domains\Doctors\Models\Doctor;
use App\Enums\GenderEnum;
use App\Enums\ImageTypeEnum;
use App\Enums\SpecializationEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class PatchDoctorRequest extends FormRequest
{
    public function rules(): array
    {
        $doctor = $this->route('doctor');
        $userId = $doctor instanceof Doctor ? $doctor->user_id : null;

        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'username' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($userId)],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'address' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'gender' => ['sometimes', 'required', Rule::enum(GenderEnum::class)],
            'birthday_date' => ['sometimes', 'nullable', 'date'],
            'specialization' => ['sometimes', 'required', Rule::enum(SpecializationEnum::class)],
            'experience_months' => ['sometimes', 'required', 'integer', 'min:0', 'max:1200'],
            'file' => ['sometimes', 'nullable', 'image', 'max:' . ImageTypeEnum::User->maxSize(), 'mimes:jpg,jpeg,png,webp'],
        ];
    }
}
