<?php

namespace App\Domains\Locations\Actions;

use App\Domains\Locations\DTOs\CityData;
use App\Domains\Locations\Models\City;

class UpdateCityAction
{
    public function execute(City $city, CityData $data): City
    {
        $city->update($data->toArray());

        return $city->fresh();
    }
}
