<?php

namespace App\Domains\Ratings\Requests;

use App\Enums\RatingTypeEnum;
use Illuminate\Foundation\Http\FormRequest;

class StoreRatingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:' . implode(',', RatingTypeEnum::values())],
            'rateable_id' => ['required_if:type,user', 'string'],
            'rateable_type' => ['required_if:type,user', 'string'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
