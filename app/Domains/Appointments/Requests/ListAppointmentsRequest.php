<?php

namespace App\Domains\Appointments\Requests;

use App\Enums\AppointmentStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ListAppointmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable'],
            'status.*' => [new Enum(AppointmentStatusEnum::class)],
            'date' => ['nullable', 'date'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'from_time' => ['nullable', 'date_format:H:i'],
            'to_time' => ['nullable', 'date_format:H:i'],
            'doctor_id' => ['nullable'],
            'doctor_id.*' => ['string', 'uuid'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'order_by' => ['nullable', 'string', 'in:created_at,appointment_date,start_time'],
            'order_dir' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
