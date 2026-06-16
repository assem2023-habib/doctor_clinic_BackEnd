<?php

namespace Database\Factories;

use App\Domains\Appointments\Models\Appointment;
use App\Domains\Doctors\Models\Doctor;
use App\Domains\Patients\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        return [
            'id' => Uuid::uuid7()->toString(),
            'doctor_id' => Doctor::factory(),
            'patient_id' => Patient::factory(),
            'appointment_date' => fake()->dateTimeBetween('-1 month', '+2 months')->format('Y-m-d'),
            'start_time' => fake()->randomElement(['09:00', '10:00', '11:00', '14:00', '15:00']),
            'end_time' => fake()->randomElement(['09:30', '10:30', '11:30', '14:30', '15:30']),
            'status' => fake()->randomElement(['pending', 'requested', 'set', 'accepted', 'completed', 'cancelled']),
            'reason' => fake()->sentence(),
            'notes' => fake()->optional()->text(),
            'created_by' => User::factory(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Appointment $appointment) {
            if ($appointment->created_by === null) {
                $appointment->created_by = $appointment->patient?->user_id ?? User::factory()->create()->id;
            }
        });
    }
}
