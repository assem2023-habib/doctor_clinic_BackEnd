<?php

namespace App\Domains\Prescriptions\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePrescriptionItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'doctor']) ?? false;
    }

    public function rules(): array
    {
        return [
            'medicine_id' => ['sometimes', 'required', 'string', 'exists:medicines,id'],
            'dosage' => ['sometimes', 'required', 'string', 'max:255'],
            'frequency' => ['sometimes', 'required', 'string', 'max:255'],
            'duration' => ['sometimes', 'required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
