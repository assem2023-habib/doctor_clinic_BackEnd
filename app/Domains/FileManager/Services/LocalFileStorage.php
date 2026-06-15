<?php

namespace App\Domains\FileManager\Services;

use App\Domains\FileManager\Contracts\FileStorageInterface;
use DateTimeInterface;
use Illuminate\Support\Facades\Storage;

class LocalFileStorage implements FileStorageInterface
{
    public function __construct(
        private readonly string $disk = 'local',
    ) {}

    public function store(string $sourcePath, string $relativePath): string
    {
        Storage::disk($this->disk)->put(
            $relativePath,
            file_get_contents($sourcePath),
        );

        return $relativePath;
    }

    public function retrieve(string $relativePath): ?string
    {
        $fullPath = Storage::disk($this->disk)->path($relativePath);

        return file_exists($fullPath) ? $fullPath : null;
    }

    public function delete(string $relativePath): bool
    {
        if (! Storage::disk($this->disk)->exists($relativePath)) {
            return false;
        }

        return Storage::disk($this->disk)->delete($relativePath);
    }

    public function exists(string $relativePath): bool
    {
        return Storage::disk($this->disk)->exists($relativePath);
    }

    public function mimeType(string $relativePath): string
    {
        return Storage::disk($this->disk)->mimeType($relativePath);
    }

    public function size(string $relativePath): int
    {
        return Storage::disk($this->disk)->size($relativePath);
    }

    public function temporaryUrl(string $relativePath, DateTimeInterface $expiry): string
    {
        return Storage::disk($this->disk)->temporaryUrl($relativePath, $expiry);
    }
}
