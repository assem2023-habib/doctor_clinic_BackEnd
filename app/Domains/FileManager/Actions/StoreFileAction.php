<?php

namespace App\Domains\FileManager\Actions;

use App\Domains\FileManager\DTOs\FileData;
use App\Domains\FileManager\Models\File;
use App\Domains\FileManager\Services\FileStorageService;
use App\Enums\FileUploadStatusEnum;
use App\Enums\StorageDiskEnum;
use Ramsey\Uuid\Uuid;

class StoreFileAction
{
    public function __construct(
        private readonly FileStorageService $fileStorage,
    ) {}

    public function execute(FileData $data, string $userId): File
    {
        $extension = $data->file->extension();
        $filename = Uuid::uuid7()->toString() . '.' . $extension;
        $relativePath = "files/{$data->medicalRecordId}/{$filename}";

        $driver = $this->fileStorage->driver(StorageDiskEnum::Local->value);
        $driver->store($data->file->getRealPath(), $relativePath);

        $checksum = hash_file('sha256', $data->file->getRealPath());

        return File::create([
            'user_id' => $userId,
            'medical_record_id' => $data->medicalRecordId,
            'original_name' => $data->originalName,
            'mime_type' => $data->mimeType,
            'size' => $data->size,
            'path' => $relativePath,
            'disk' => StorageDiskEnum::Local,
            'checksum' => $checksum,
            'upload_status' => FileUploadStatusEnum::Completed,
            'file_category' => $data->fileCategory,
            'total_chunks' => 1,
            'metadata' => null,
        ]);
    }
}
