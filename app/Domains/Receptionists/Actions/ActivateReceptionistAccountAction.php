<?php

namespace App\Domains\Receptionists\Actions;

use App\Domains\Receptionists\Models\Receptionist;

class ActivateReceptionistAccountAction
{
    public function execute(Receptionist $receptionist): Receptionist
    {
        $receptionist->user->update(['is_active' => true]);
        $receptionist->load('user.roles');

        return $receptionist;
    }
}
