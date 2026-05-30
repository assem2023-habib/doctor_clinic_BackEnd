<?php

namespace App\Domains\Auth\Requests;

use App\Enums\GenderEnum;
use App\Enums\ImageTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterDoctorRequest extends FormRequest
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
            'city_id' => ['nullable', 'string', 'exists:cities,id'],
            'specialization_id' => ['required', 'string', 'exists:specializations,id'],
            'experience_months' => ['required', 'integer', 'min:0', 'max:1200'],
            'password' => ['required', Password::defaults()],
            'file' => ['nullable', 'image', 'max:' . ImageTypeEnum::User->maxSize(), 'mimes:jpg,jpeg,png,webp'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => __('auth_messages.first_name_required'),
            'first_name.string' => __('auth_messages.first_name_string'),
            'first_name.max' => __('auth_messages.first_name_max'),

            'last_name.required' => __('auth_messages.last_name_required'),
            'last_name.string' => __('auth_messages.last_name_string'),
            'last_name.max' => __('auth_messages.last_name_max'),

            'username.required' => __('auth_messages.username_required'),
            'username.string' => __('auth_messages.username_string'),
            'username.max' => __('auth_messages.username_max'),
            'username.unique' => __('auth_messages.username_unique'),

            'email.required' => __('auth_messages.email_required'),
            'email.email' => __('auth_messages.email_email'),
            'email.max' => __('auth_messages.email_max'),
            'email.unique' => __('auth_messages.email_unique'),

            'phone.string' => __('auth_messages.phone_string'),
            'phone.max' => __('auth_messages.phone_max'),

            'address.string' => __('auth_messages.address_string'),
            'address.max' => __('auth_messages.address_max'),

            'gender.required' => __('auth_messages.gender_required'),
            'gender.*' => __('auth_messages.gender_enum'),

            'birthday_date.date' => __('auth_messages.birthday_date'),

            'password.required' => __('auth_messages.password_required'),
        ];
    }
}
