<?php

namespace App\Domains\FileManager\Services;

use App\Domains\FileManager\Access\AdminHandler;
use App\Domains\FileManager\Access\FileAccessHandler;
use App\Domains\FileManager\Access\OwnerHandler;
use App\Domains\FileManager\Access\SupervisorDoctorHandler;
use App\Domains\FileManager\Access\TreatingDoctorHandler;
use App\Domains\FileManager\Models\File;
use App\Models\User;

class FileAccessService
{
    private FileAccessHandler $chain;

    public function __construct()
    {
        $owner = new OwnerHandler();
        $doctor = new TreatingDoctorHandler();
        $supervisor = new SupervisorDoctorHandler();
        $admin = new AdminHandler();

        $owner->setNext($doctor)
            ->setNext($supervisor)
            ->setNext($admin);

        $this->chain = $owner;
    }

    public function canAccess(User $user, File $file): bool
    {
        $result = $this->chain->handle($user, $file);

        return $result ?? false;
    }
}
