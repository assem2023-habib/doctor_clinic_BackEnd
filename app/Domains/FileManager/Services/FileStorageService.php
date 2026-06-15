<?php

namespace App\Domains\FileManager\Services;

use App\Domains\FileManager\Contracts\FileStorageInterface;

class FileStorageService
{
    private array $drivers = [];

    public function __construct()
    {
        $this->drivers['local'] = app(LocalFileStorage::class);
    }

    public function driver(?string $disk = null): FileStorageInterface
    {
        $disk ??= config('files.default_disk', 'local');

        if (! isset($this->drivers[$disk])) {
            throw new \RuntimeException("Unknown storage disk: {$disk}");
        }

        return $this->drivers[$disk];
    }
}
