<?php

namespace Database\Factories;

use App\Domains\Doctors\Models\Doctor;
use App\Domains\Doctors\Models\Specialization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

class DoctorFactory extends Factory
{
    protected $model = Doctor::class;

    public function definition(): array
    {
        return [
            'id' => Uuid::uuid7()->toString(),
            'user_id' => User::factory(),
            'specialization_id' => Specialization::factory(),
            'experience_months' => fake()->numberBetween(1, 360),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Doctor $doctor) {
            $doctor->user->assignRole('doctor');
        });
    }
}
