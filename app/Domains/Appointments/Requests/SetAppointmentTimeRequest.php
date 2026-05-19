<?php

namespace App\Domains\Appointments\Requests;

use App\Domains\Appointments\Rules\NoOverlappingAppointment;
use Illuminate\Foundation\Http\FormRequest;

class SetAppointmentTimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $appointment = $this->route('appointment');

        return [
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => [
                'required',
                'date_format:H:i',
                new NoOverlappingAppointment(
                    doctorId: $appointment->doctor_id,
                    date: $this->input('appointment_date'),
                    startTime: $this->input('start_time'),
                    endTime: $this->input('end_time'),
                    excludeAppointmentId: $appointment->id,
                ),
            ],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ];
    }
}
