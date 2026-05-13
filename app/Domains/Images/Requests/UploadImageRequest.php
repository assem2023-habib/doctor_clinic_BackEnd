<?php

namespace App\Domains\Images\Requests;

use App\Enums\ImageTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadImageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
            'type' => ['required', Rule::enum(ImageTypeEnum::class)],
            'imageable_id' => ['required', 'string'],
        ];
    }
}
