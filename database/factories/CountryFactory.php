<?php

namespace Database\Factories;

use App\Domains\Locations\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

class CountryFactory extends Factory
{
    protected $model = Country::class;

    public function definition(): array
    {
        return [
            'id' => Uuid::uuid7()->toString(),
            'name' => ['en' => fake()->country(), 'ar' => fake()->country()],
            'code' => fake()->unique()->countryCode(),
            'flag' => null,
        ];
    }
}
