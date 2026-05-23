<?php

namespace App\Domains\Supervisions\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupervisionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'doctor_id' => ['required', 'string', 'exists:doctors,id'],
        ];
    }
}
