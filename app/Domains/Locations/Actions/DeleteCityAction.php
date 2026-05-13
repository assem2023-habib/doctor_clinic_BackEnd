<?php

namespace App\Domains\Locations\Actions;

use App\Domains\Locations\Models\City;

class DeleteCityAction
{
    public function execute(City $city): void
    {
        $city->delete();
    }
}
