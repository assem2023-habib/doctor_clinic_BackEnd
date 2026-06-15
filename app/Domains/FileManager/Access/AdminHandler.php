<?php

namespace App\Domains\FileManager\Access;

use App\Domains\FileManager\Models\File;
use App\Models\User;

class AdminHandler extends AbstractFileAccessHandler
{
    public function handle(User $user, File $file): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $this->next($user, $file);
    }
}
