<?php

namespace App\Domains\Appointments\Requests;

use App\Domains\Appointments\Rules\WithinDoctorSchedule;
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
            'preferred_date' => [
                'nullable',
                'date',
                'after_or_equal:today',
                new WithinDoctorSchedule(
                    doctorId: $this->input('doctor_id'),
                    date: $this->input('preferred_date'),
                ),
            ],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
