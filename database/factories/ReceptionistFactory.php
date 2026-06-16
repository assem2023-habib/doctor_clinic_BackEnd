<?php

namespace Database\Factories;

use App\Domains\Receptionists\Models\Receptionist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

class ReceptionistFactory extends Factory
{
    protected $model = Receptionist::class;

    public function definition(): array
    {
        return [
            'id' => Uuid::uuid7()->toString(),
            'user_id' => User::factory(),
            'shift_start' => fake()->randomElement(['08:00', '09:00', '10:00']),
            'shift_end' => fake()->randomElement(['16:00', '17:00', '18:00']),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Receptionist $receptionist) {
            $receptionist->user->assignRole('receptionist');
        });
    }
}
