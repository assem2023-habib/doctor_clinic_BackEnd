<?php

namespace App\Domains\MedicalRecords\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferMedicalRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'doctor_id' => ['required', 'string', 'exists:doctors,id'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
