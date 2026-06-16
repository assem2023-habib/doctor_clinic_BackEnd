<?php

namespace Database\Factories;

use App\Domains\MedicalRecords\Models\MedicalRecord;
use App\Domains\Doctors\Models\Doctor;
use App\Domains\Patients\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

class MedicalRecordFactory extends Factory
{
    protected $model = MedicalRecord::class;

    public function definition(): array
    {
        return [
            'id' => Uuid::uuid7()->toString(),
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'diagnosis' => fake()->sentence(),
            'notes' => fake()->optional()->text(),
            'created_at' => fake()->dateTimeThisYear(),
        ];
    }
}
