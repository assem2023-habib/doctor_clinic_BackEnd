<?php

namespace App\Domains\Locations\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCountryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'size:2', Rule::unique('countries', 'code')->ignore($this->route('country'))],
            'flag' => ['nullable', 'string', 'max:500'],
        ];
    }
}
