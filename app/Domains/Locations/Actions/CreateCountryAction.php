<?php

namespace App\Domains\Locations\Actions;

use App\Domains\Locations\DTOs\CountryData;
use App\Domains\Locations\Models\Country;

class CreateCountryAction
{
    public function execute(CountryData $data): Country
    {
        return Country::create($data->toArray());
    }
}
