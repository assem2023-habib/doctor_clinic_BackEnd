<?php

namespace App\Domains\FileManager\Access;

use App\Domains\FileManager\Models\File;
use App\Models\User;

interface FileAccessHandler
{
    public function setNext(FileAccessHandler $handler): FileAccessHandler;

    public function handle(User $user, File $file): ?bool;
}
