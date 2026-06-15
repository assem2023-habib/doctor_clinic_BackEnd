<?php

namespace App\Domains\FileManager\Actions;

use App\Domains\FileManager\Models\File;
use App\Domains\FileManager\Services\FileStorageService;

class DeleteFileAction
{
    public function __construct(
        private readonly FileStorageService $fileStorage,
    ) {}

    public function execute(File $file): void
    {
        if ($file->path !== null) {
            $driver = $this->fileStorage->driver($file->disk?->value ?? 'local');
            $driver->delete($file->path);
        }

        $file->delete();
    }
}
