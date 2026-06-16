<?php

namespace Database\Factories;

use App\Domains\Locations\Models\City;
use App\Domains\Locations\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

class CityFactory extends Factory
{
    protected $model = City::class;

    public function definition(): array
    {
        return [
            'id' => Uuid::uuid7()->toString(),
            'name' => ['en' => fake()->city(), 'ar' => fake()->city()],
            'country_id' => Country::factory(),
        ];
    }
}
