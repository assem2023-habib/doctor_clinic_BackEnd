<?php

namespace App\Domains\FileManager\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteUploadRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'checksum' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]{64}$/i'],
        ];
    }
}
