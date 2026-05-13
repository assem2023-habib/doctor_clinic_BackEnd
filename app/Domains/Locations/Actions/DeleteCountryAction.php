<?php

namespace App\Domains\Locations\Actions;

use App\Domains\Locations\Models\Country;

class DeleteCountryAction
{
    public function execute(Country $country): void
    {
        $country->delete();
    }
}
