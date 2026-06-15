<?php

namespace App\Domains\FileManager\Actions;

use App\Domains\FileManager\Models\File;
use App\Domains\FileManager\Services\ChunkStorageService;
use App\Enums\FileUploadStatusEnum;
use Illuminate\Http\UploadedFile;

class UploadChunkAction
{
    public function __construct(
        private readonly ChunkStorageService $chunkStorage,
    ) {}

    public function execute(File $file, UploadedFile $chunk, int $chunkIndex): File
    {
        $this->chunkStorage->storeChunk($file->id, $chunkIndex, $chunk);

        $totalChunks = max($file->total_chunks, $chunkIndex + 1);

        $file->update([
            'total_chunks' => $totalChunks,
            'upload_status' => FileUploadStatusEnum::Uploading,
        ]);

        return $file->fresh();
    }
}
