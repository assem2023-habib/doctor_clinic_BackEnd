<?php

namespace App\Domains\Locations\Actions;

use App\Domains\Locations\DTOs\CountryData;
use App\Domains\Locations\Models\Country;

class UpdateCountryAction
{
    public function execute(Country $country, CountryData $data): Country
    {
        $country->update($data->toArray());

        return $country->fresh();
    }
}
