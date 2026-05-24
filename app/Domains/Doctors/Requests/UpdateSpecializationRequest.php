<?php

namespace App\Domains\Doctors\Requests;

use App\Enums\ImageTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSpecializationRequest extends FormRequest
{
    public function rules(): array
    {
        $specialization = $this->route('specialization');
        $maxSize = ImageTypeEnum::Specialization->maxSize();

        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255', Rule::unique('specializations', 'name->en')->ignore($specialization)],
            'description_ar' => ['nullable', 'string', 'max:1000'],
            'description_en' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'file' => ['nullable', 'image', "max:{$maxSize}", 'mimes:jpg,jpeg,png,webp'],
        ];
    }
}
