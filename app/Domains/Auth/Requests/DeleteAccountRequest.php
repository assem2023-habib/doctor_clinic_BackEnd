<?php

namespace App\Domains\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteAccountRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'current_password'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'Please enter your password to confirm account deletion.',
            'password.string' => 'Password must be a valid text.',
            'password.current_password' => 'The password you entered is incorrect.',
        ];
    }
}
