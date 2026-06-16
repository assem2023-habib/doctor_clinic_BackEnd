<?php

namespace Database\Factories;

use App\Domains\MedicalRecords\Models\MedicalRecord;
use App\Domains\Prescriptions\Models\Prescription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

class PrescriptionFactory extends Factory
{
    protected $model = Prescription::class;

    public function definition(): array
    {
        return [
            'id' => Uuid::uuid7()->toString(),
            'medical_record_id' => MedicalRecord::factory(),
            'prescription_date' => fake()->date(),
            'status' => fake()->randomElement(['active', 'archived', 'expired']),
            'notes' => fake()->optional()->text(),
        ];
    }
}
