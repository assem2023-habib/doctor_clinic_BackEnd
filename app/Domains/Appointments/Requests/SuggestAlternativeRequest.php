<?php

namespace App\Domains\Appointments\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SuggestAlternativeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:2000'],
        ];
    }
}
