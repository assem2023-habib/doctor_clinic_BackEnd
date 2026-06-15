<?php

namespace App\Domains\FileManager\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ChunkStorageService
{
    private const CHUNKS_DIR = 'chunks';

    private string $disk;

    public function __construct()
    {
        $this->disk = config('files.default_disk', 'local');
    }

    public function storeChunk(string $uploadId, int $chunkIndex, UploadedFile $file): void
    {
        $chunkDir = $this->chunkDir($uploadId);

        $file->storeAs($chunkDir, "chunk_{$chunkIndex}", $this->disk);
    }

    public function chunkExists(string $uploadId, int $chunkIndex): bool
    {
        return Storage::disk($this->disk)->exists(
            $this->chunkPath($uploadId, $chunkIndex)
        );
    }

    public function assembleChunks(string $uploadId, int $totalChunks, string $destinationPath): void
    {
        $chunkDir = $this->chunkDir($uploadId);
        $tempPath = Storage::disk($this->disk)->path($destinationPath);
        $parentDir = dirname($tempPath);

        if (! File::isDirectory($parentDir)) {
            File::makeDirectory($parentDir, 0755, true);
        }

        $handle = fopen($tempPath, 'wb');

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkFullPath = Storage::disk($this->disk)->path(
                "{$chunkDir}/chunk_{$i}"
            );

            if (! file_exists($chunkFullPath)) {
                fclose($handle);
                throw new \RuntimeException("Missing chunk {$i} for upload {$uploadId}");
            }

            $chunkContent = file_get_contents($chunkFullPath);
            fwrite($handle, $chunkContent);
        }

        fclose($handle);
    }

    public function cleanupChunks(string $uploadId): void
    {
        $chunkDir = $this->chunkDir($uploadId);

        Storage::disk($this->disk)->deleteDirectory($chunkDir);
    }

    public function getChunkSize(string $uploadId, int $chunkIndex): int
    {
        return Storage::disk($this->disk)->size(
            $this->chunkPath($uploadId, $chunkIndex)
        );
    }

    private function chunkDir(string $uploadId): string
    {
        return self::CHUNKS_DIR . '/' . $uploadId;
    }

    private function chunkPath(string $uploadId, int $chunkIndex): string
    {
        return "{$this->chunkDir($uploadId)}/chunk_{$chunkIndex}";
    }
}
