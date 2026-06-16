<?php

namespace Database\Factories;

use App\Domains\Prescriptions\Models\Medicine;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

class MedicineFactory extends Factory
{
    protected $model = Medicine::class;

    public function definition(): array
    {
        return [
            'id' => Uuid::uuid7()->toString(),
            'name' => ['en' => fake()->unique()->word(), 'ar' => fake()->word()],
            'description' => ['en' => fake()->sentence(), 'ar' => fake()->sentence()],
            'barcode' => fake()->optional()->ean13(),
            'manufacturer' => fake()->company(),
            'created_by' => null,
        ];
    }
}
