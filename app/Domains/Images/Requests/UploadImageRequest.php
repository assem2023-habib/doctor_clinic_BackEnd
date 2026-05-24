<?php

namespace App\Domains\Images\Requests;

use App\Enums\ImageTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadImageRequest extends FormRequest
{
    public function rules(): array
    {
        $type = $this->input('type') ? ImageTypeEnum::tryFrom($this->input('type')) : null;
        $maxSize = $type ? $type->maxSize() : 2048;

        $rules = [
            'file' => ['required', 'image', "max:{$maxSize}", 'mimes:jpg,jpeg,png,webp'],
            'type' => ['required', Rule::enum(ImageTypeEnum::class)],
            'imageable_id' => ['required', 'string'],
        ];

        if ($type === ImageTypeEnum::Specialization) {
            $rules['imageable_id'][] = Rule::exists('specializations', 'id');
        }

        return $rules;
    }
}
