<?php

namespace App\Domains\Doctors\Actions;

use App\Domains\Doctors\Models\Specialization;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class DeleteSpecializationAction
{
    public function execute(Specialization $specialization): void
    {
        if ($specialization->doctors()->count() > 0) {
            throw new ConflictHttpException(__('Cannot delete specialization with associated doctors'));
        }

        $specialization->delete();
    }
}
