<?php

namespace Database\Factories;

use App\Domains\Ratings\Models\Rating;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

class RatingFactory extends Factory
{
    protected $model = Rating::class;

    public function definition(): array
    {
        return [
            'id' => Uuid::uuid7()->toString(),
            'rater_id' => User::factory(),
            'type' => fake()->randomElement(['user', 'service', 'center', 'appointment_system']),
            'rateable_id' => User::factory(),
            'rateable_type' => 'App\Models\User',
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->optional()->sentence(),
        ];
    }
}
