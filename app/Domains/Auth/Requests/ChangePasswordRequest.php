<?php

namespace App\Domains\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'old_password' => ['required', 'string', 'current_password'],
            'new_password' => ['required', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'old_password.required' => __('auth_messages.old_password_required'),
            'old_password.string' => __('auth_messages.old_password_string'),
            'old_password.current_password' => __('auth_messages.old_password_current_password'),

            'new_password.required' => __('auth_messages.new_password_required'),
        ];
    }
}
