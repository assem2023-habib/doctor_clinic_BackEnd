<?php

namespace Database\Factories;

use App\Domains\Prescriptions\Models\Prescription;
use App\Domains\Prescriptions\Models\PrescriptionItem;
use App\Domains\Prescriptions\Models\Medicine;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

class PrescriptionItemFactory extends Factory
{
    protected $model = PrescriptionItem::class;

    public function definition(): array
    {
        return [
            'id' => Uuid::uuid7()->toString(),
            'prescription_id' => Prescription::factory(),
            'medicine_id' => Medicine::factory(),
            'dosage' => fake()->randomElement(['500mg', '250mg', '1g', '10mg', '5mg']),
            'frequency' => fake()->randomElement(['once daily', 'twice daily', 'three times daily', 'every 8 hours', 'as needed']),
            'duration' => fake()->randomElement(['7 days', '10 days', '14 days', '30 days', '3 months']),
            'instructions' => fake()->optional()->sentence(),
        ];
    }
}
