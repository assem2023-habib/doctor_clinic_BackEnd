<?php

namespace App\Domains\Supervisions\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkAssignPatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_ids' => ['required', 'array', 'min:1'],
            'patient_ids.*' => ['required', 'string', 'exists:patients,user_id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
