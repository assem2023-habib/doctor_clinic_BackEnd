<?php

namespace App\Domains\Doctors\Actions;

use App\Domains\Doctors\Models\Specialization;
use App\Domains\Images\Actions\DeleteImageAction;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class DeleteSpecializationAction
{
    public function __construct(
        private readonly DeleteImageAction $deleteImageAction,
    ) {}

    public function execute(Specialization $specialization): void
    {
        if ($specialization->doctors()->count() > 0) {
            throw new ConflictHttpException(__('Cannot delete specialization with associated doctors'));
        }

        if ($specialization->image) {
            $this->deleteImageAction->execute($specialization->image);
        }

        $specialization->delete();
    }
}
