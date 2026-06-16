<?php

namespace Database\Factories;

use App\Domains\Doctors\Models\Specialization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class SpecializationFactory extends Factory
{
    protected $model = Specialization::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'id' => Uuid::uuid7()->toString(),
            'name' => ['en' => $name, 'ar' => $name],
            'slug' => Str::slug($name),
            'description' => ['en' => fake()->sentence(), 'ar' => fake()->sentence()],
            'is_active' => true,
        ];
    }
}
