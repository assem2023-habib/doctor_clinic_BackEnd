<?php

namespace App\Domains\Appointments\Requests;

use App\Domains\Doctors\Models\Doctor;
use Illuminate\Foundation\Http\FormRequest;

class RequestAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doctor_id' => ['required', 'string', 'exists:doctors,id'],
            'preferred_date' => ['nullable', 'date', 'after_or_equal:today'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
