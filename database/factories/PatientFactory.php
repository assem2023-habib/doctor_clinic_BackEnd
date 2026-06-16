<?php

namespace Database\Factories;

use App\Domains\Patients\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        return [
            'id' => Uuid::uuid7()->toString(),
            'user_id' => User::factory(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Patient $patient) {
            $patient->user->assignRole('patient');
        });
    }
}
