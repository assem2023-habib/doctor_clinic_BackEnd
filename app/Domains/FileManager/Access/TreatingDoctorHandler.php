<?php

namespace App\Domains\FileManager\Access;

use App\Domains\FileManager\Models\File;
use App\Models\User;

class TreatingDoctorHandler extends AbstractFileAccessHandler
{
    public function handle(User $user, File $file): ?bool
    {
        $doctorUserId = $file->medicalRecord->doctor->user_id;

        if ($doctorUserId === $user->id) {
            return true;
        }

        return $this->next($user, $file);
    }
}
