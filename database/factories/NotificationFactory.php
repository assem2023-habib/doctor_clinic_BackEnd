<?php

namespace Database\Factories;

use App\Domains\Notifications\Models\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'id' => Uuid::uuid7()->toString(),
            'topic' => fake()->randomElement(['appointment', 'system', 'promotion', 'reminder']),
            'title' => fake()->sentence(4),
            'body' => ['message' => fake()->paragraph(), 'action_url' => fake()->optional()->url()],
        ];
    }
}
