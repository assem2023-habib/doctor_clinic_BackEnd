<?php

namespace App\Domains\FileManager\Access;

use App\Domains\FileManager\Models\File;
use App\Models\User;

class OwnerHandler extends AbstractFileAccessHandler
{
    public function handle(User $user, File $file): ?bool
    {
        $patientUserId = $file->medicalRecord->patient->user_id;

        if ($patientUserId === $user->id) {
            return true;
        }

        return $this->next($user, $file);
    }
}
