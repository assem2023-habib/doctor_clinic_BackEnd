<?php

namespace App\Domains\Prescriptions\Requests;

use App\Domains\Prescriptions\Enums\PrescriptionStatusEnum;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'doctor']) ?? false;
    }

    public function rules(): array
    {
        return [
            'prescription_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:' . implode(',', PrescriptionStatusEnum::values())],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
