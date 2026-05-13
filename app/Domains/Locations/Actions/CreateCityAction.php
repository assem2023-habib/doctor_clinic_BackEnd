<?php

namespace App\Domains\Locations\Actions;

use App\Domains\Locations\DTOs\CityData;
use App\Domains\Locations\Models\City;

class CreateCityAction
{
    public function execute(CityData $data): City
    {
        return City::create($data->toArray());
    }
}
