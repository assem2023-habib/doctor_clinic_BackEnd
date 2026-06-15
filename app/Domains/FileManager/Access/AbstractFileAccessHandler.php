<?php

namespace App\Domains\FileManager\Access;

use App\Domains\FileManager\Models\File;
use App\Models\User;

abstract class AbstractFileAccessHandler implements FileAccessHandler
{
    private ?FileAccessHandler $next = null;

    public function setNext(FileAccessHandler $handler): FileAccessHandler
    {
        $this->next = $handler;

        return $handler;
    }

    protected function next(User $user, File $file): ?bool
    {
        if ($this->next === null) {
            return null;
        }

        return $this->next->handle($user, $file);
    }
}
