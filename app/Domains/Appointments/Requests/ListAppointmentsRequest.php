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
            'status' => ['nullable', new Enum(AppointmentStatusEnum::class)],
            'date' => ['nullable', 'date'],
            'doctor_id' => ['nullable', 'string', 'uuid'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
