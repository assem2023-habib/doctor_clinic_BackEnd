<?php

namespace App\Domains\FileManager\Actions;

use App\Domains\FileManager\Models\File;
use App\Domains\FileManager\Services\ChunkStorageService;
use App\Domains\FileManager\Services\FileStorageService;
use App\Enums\FileUploadStatusEnum;
use Ramsey\Uuid\Uuid;

class AssembleChunksAction
{
    public function __construct(
        private readonly ChunkStorageService $chunkStorage,
        private readonly FileStorageService $fileStorage,
    ) {}

    public function execute(File $file, string $checksum): File
    {
        $extension = pathinfo($file->original_name, PATHINFO_EXTENSION);
        $relativePath = sprintf(
            'files/%s/%s.%s',
            $file->medical_record_id,
            Uuid::uuid7()->toString(),
            $extension
        );

        $this->chunkStorage->assembleChunks(
            $file->id,
            $file->total_chunks,
            $relativePath,
        );

        $driver = $this->fileStorage->driver($file->disk?->value ?? 'local');

        $fileSize = $driver->size($relativePath);

        if (! hash_equals($checksum, hash_file('sha256', $driver->retrieve($relativePath)))) {
            $driver->delete($relativePath);
            $this->chunkStorage->cleanupChunks($file->id);

            $file->update(['upload_status' => FileUploadStatusEnum::Failed]);

            throw new \RuntimeException('Checksum verification failed');
        }

        $this->chunkStorage->cleanupChunks($file->id);

        $file->update([
            'path' => $relativePath,
            'size' => $fileSize,
            'checksum' => $checksum,
            'upload_status' => FileUploadStatusEnum::Completed,
        ]);

        return $file->fresh();
    }
}
