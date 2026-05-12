<?php

namespace App\Domains\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => __('auth_messages.email_required'),
            'email.email' => __('auth_messages.email_email'),
            'email.exists' => __('auth_messages.email_exists'),

            'password.required' => __('auth_messages.password_required'),
            'password.string' => __('auth_messages.password_string'),
        ];
    }
}
