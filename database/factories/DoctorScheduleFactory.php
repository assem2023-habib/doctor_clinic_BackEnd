<?php

namespace Database\Factories;

use App\Domains\Doctors\Models\Doctor;
use App\Domains\Doctors\Models\DoctorSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

class DoctorScheduleFactory extends Factory
{
    protected $model = DoctorSchedule::class;

    public function definition(): array
    {
        return [
            'id' => Uuid::uuid7()->toString(),
            'doctor_id' => Doctor::factory(),
            'day_of_week' => fake()->randomElement(['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']),
            'start_time' => fake()->randomElement(['09:00', '10:00', '11:00']),
            'end_time' => fake()->randomElement(['16:00', '17:00', '18:00']),
            'is_active' => true,
        ];
    }
}
