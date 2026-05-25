<?php

namespace App\Domains\Prescriptions\Requests;

use App\Domains\Prescriptions\Enums\PrescriptionStatusEnum;
use Illuminate\Foundation\Http\FormRequest;

class StorePrescriptionRequest extends FormRequest
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
            'items' => ['nullable', 'array', 'max:50'],
            'items.*.medicine_id' => ['required', 'string', 'exists:medicines,id'],
            'items.*.dosage' => ['required', 'string', 'max:255'],
            'items.*.frequency' => ['required', 'string', 'max:255'],
            'items.*.duration' => ['required', 'string', 'max:255'],
            'items.*.instructions' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
