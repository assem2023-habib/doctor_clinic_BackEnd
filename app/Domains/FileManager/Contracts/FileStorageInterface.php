<?php

namespace App\Domains\FileManager\Contracts;

use DateTimeInterface;

interface FileStorageInterface
{
    public function store(string $sourcePath, string $relativePath): string;

    public function retrieve(string $relativePath): ?string;

    public function delete(string $relativePath): bool;

    public function exists(string $relativePath): bool;

    public function mimeType(string $relativePath): string;

    public function size(string $relativePath): int;

    public function temporaryUrl(string $relativePath, DateTimeInterface $expiry): string;
}
