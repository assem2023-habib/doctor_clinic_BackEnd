<?php

namespace App\Domains\Doctors\Actions;

use App\Domains\Doctors\DTOs\SpecializationData;
use App\Domains\Doctors\Models\Specialization;

class CreateSpecializationAction
{
    public function execute(SpecializationData $data): Specialization
    {
        return Specialization::create($data->toArray());
    }
}
