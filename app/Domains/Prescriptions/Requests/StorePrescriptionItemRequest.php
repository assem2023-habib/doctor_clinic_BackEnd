<?php

namespace App\Domains\Prescriptions\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePrescriptionItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'doctor']) ?? false;
    }

    public function rules(): array
    {
        return [
            'medicine_id' => ['required', 'string', 'exists:medicines,id'],
            'dosage' => ['required', 'string', 'max:255'],
            'frequency' => ['required', 'string', 'max:255'],
            'duration' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
