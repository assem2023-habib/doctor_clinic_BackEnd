<?php

namespace App\Domains\FileManager\Actions;

use App\Domains\FileManager\DTOs\FileData;
use App\Domains\FileManager\Models\File;
use App\Enums\FileUploadStatusEnum;
use App\Enums\StorageDiskEnum;

class InitChunkedUploadAction
{
    public function execute(FileData $data, string $userId): File
    {
        $totalChunks = config('files.chunk_size', 5242880);

        return File::create([
            'user_id' => $userId,
            'medical_record_id' => $data->medicalRecordId,
            'original_name' => $data->originalName,
            'mime_type' => $data->mimeType,
            'size' => $data->size,
            'path' => null,
            'disk' => StorageDiskEnum::Local,
            'checksum' => $data->checksum,
            'upload_status' => FileUploadStatusEnum::Uploading,
            'file_category' => $data->fileCategory,
            'total_chunks' => 0,
            'metadata' => null,
        ]);
    }
}
