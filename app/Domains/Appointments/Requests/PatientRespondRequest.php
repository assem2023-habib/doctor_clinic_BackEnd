<?php

namespace App\Domains\Appointments\Requests;

use App\Domains\Appointments\Enums\PatientResponseEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class PatientRespondRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'response' => ['required', new Enum(PatientResponseEnum::class)],
        ];
    }
}
