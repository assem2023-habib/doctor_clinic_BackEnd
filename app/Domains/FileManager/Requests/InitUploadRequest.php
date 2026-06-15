<?php

namespace App\Domains\FileManager\Requests;

use App\Domains\MedicalRecords\Models\MedicalRecord;
use App\Enums\FileCategoryEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitUploadRequest extends FormRequest
{
    public function rules(): array
    {
        $maxFileSizeBytes = config('files.max_file_size', 20480) * 1024;

        return [
            'medical_record_id' => ['required', 'uuid', Rule::exists(MedicalRecord::class, 'id')],
            'file_category' => ['required', Rule::enum(FileCategoryEnum::class)],
            'original_name' => ['required', 'string', 'max:255'],
            'mime_type' => ['required', 'string', Rule::in(config('files.allowed_mime_types', []))],
            'file_size' => ['required', 'integer', 'min:1', "max:{$maxFileSizeBytes}"],
            'total_chunks' => ['required', 'integer', 'min:1'],
            'checksum' => ['nullable', 'string', 'size:64', 'regex:/^[a-f0-9]{64}$/i'],
        ];
    }
}
