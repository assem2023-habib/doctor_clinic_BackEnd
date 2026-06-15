<?php

namespace App\Domains\FileManager\DTOs;

use App\Enums\FileCategoryEnum;
use Illuminate\Http\UploadedFile;

class FileData
{
    private function __construct(
        public readonly string $medicalRecordId,
        public readonly FileCategoryEnum $fileCategory,
        public readonly string $originalName,
        public readonly string $mimeType,
        public readonly int $size,
        public readonly ?string $checksum = null,
        public readonly ?UploadedFile $file = null,
        public readonly ?string $path = null,
    ) {}

    public static function fromChunkedUpload(array $data): self
    {
        return new self(
            medicalRecordId: $data['medical_record_id'],
            fileCategory: FileCategoryEnum::from($data['file_category']),
            originalName: $data['original_name'],
            mimeType: $data['mime_type'],
            size: (int) $data['file_size'],
            checksum: $data['checksum'] ?? null,
        );
    }

    public static function fromDirectUpload(array $validated): self
    {
        $file = $validated['file'];

        return new self(
            medicalRecordId: $validated['medical_record_id'],
            fileCategory: FileCategoryEnum::from($validated['file_category']),
            originalName: $file->getClientOriginalName(),
            mimeType: $file->getMimeType(),
            size: $file->getSize(),
            file: $file,
        );
    }
}
