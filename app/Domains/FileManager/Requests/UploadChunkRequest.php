<?php

namespace App\Domains\FileManager\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadChunkRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'chunk' => ['required', 'file', 'max:5120'],
            'chunk_index' => ['required', 'integer', 'min:0'],
        ];
    }
}
