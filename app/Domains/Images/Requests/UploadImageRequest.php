<?php

namespace App\Domains\Images\Requests;

use App\Enums\ImageTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadImageRequest extends FormRequest
{
    public function rules(): array
    {
        $maxSize = $this->input('type')
            ? ImageTypeEnum::from($this->input('type'))->maxSize()
            : 2048;

        return [
            'file' => ['required', 'image', "max:{$maxSize}", 'mimes:jpg,jpeg,png,webp'],
            'type' => ['required', Rule::enum(ImageTypeEnum::class)],
            'imageable_id' => ['required', 'string'],
        ];
    }
}
