<?php

namespace App\Domains\Supervisions\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignPatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'string', 'exists:patients,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
