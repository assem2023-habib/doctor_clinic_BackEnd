<?php

namespace App\Domains\FileManager\Requests;

use App\Domains\MedicalRecords\Models\MedicalRecord;
use App\Enums\FileCategoryEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFileRequest extends FormRequest
{
    public function rules(): array
    {
        $maxFileSize = config('files.max_file_size', 20480);

        return [
            'file' => ['required', 'file', "max:{$maxFileSize}", 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,txt,xls,xlsx'],
            'medical_record_id' => ['required', 'uuid', Rule::exists(MedicalRecord::class, 'id')],
            'file_category' => ['required', Rule::enum(FileCategoryEnum::class)],
        ];
    }
}
