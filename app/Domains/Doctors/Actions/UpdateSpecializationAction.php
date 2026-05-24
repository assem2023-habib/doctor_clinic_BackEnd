<?php

namespace App\Domains\Doctors\Actions;

use App\Domains\Doctors\DTOs\SpecializationData;
use App\Domains\Doctors\Models\Specialization;

class UpdateSpecializationAction
{
    public function execute(Specialization $specialization, SpecializationData $data): Specialization
    {
        $specialization->update($data->toArray());

        return $specialization->fresh();
    }
}
