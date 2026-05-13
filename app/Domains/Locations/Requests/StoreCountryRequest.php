<?php

namespace App\Domains\Locations\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCountryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'size:2', 'unique:countries,code'],
            'flag' => ['nullable', 'string', 'max:500'],
        ];
    }
}
