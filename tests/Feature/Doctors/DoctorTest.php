<?php

namespace Tests\Feature\Doctors;

use App\Domains\Doctors\Models\Doctor;
use App\Enums\DayOfWeekEnum;
use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorTest extends TestCase
{
    use RefreshDatabase;

    private function createDoctor(array $overrides = []): User
    {
        $user = User::factory()->create(array_merge([
            'role' => RoleEnum::Doctor,
        ], $overrides));

        $user->doctor()->create([]);

        return $user;
    }

    private function createSchedule(Doctor $doctor, array $overrides = []): void
    {
        $doctor->schedules()->create(array_merge([
            'day_of_week' => DayOfWeekEnum::Monday,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_active' => true,
        ], $overrides));
    }

    public function test_can_list_doctors(): void
    {
        $this->createDoctor(['first_name' => 'John', 'email' => 'john@example.com']);
        $this->createDoctor(['first_name' => 'Jane', 'email' => 'jane@example.com']);

        $response = $this->getJson('/api/v1/doctors');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data',
                'meta' => ['pagination'],
            ]);

        $json = $response->json();
        $this->assertEquals(200, $json['status']);
        $this->assertCount(2, $json['data']);
    }

    public function test_doctor_list_includes_schedules_when_loaded(): void
    {
        $user = $this->createDoctor();
        $this->createSchedule($user->doctor);

        $response = $this->getJson('/api/v1/doctors');

        $response->assertStatus(200);
        $doctor = $response->json()['data'][0];
        $this->assertArrayHasKey('schedules', $doctor);
        $this->assertNotNull($doctor['schedules']);
    }

    public function test_can_show_single_doctor(): void
    {
        $user = $this->createDoctor([
            'first_name' => 'John',
            'email' => 'john@example.com',
        ]);
        $this->createSchedule($user->doctor);

        $response = $this->getJson("/api/v1/doctors/{$user->doctor->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['id', 'first_name', 'last_name', 'email', 'role', 'schedules'],
            ]);

        $json = $response->json();
        $this->assertEquals(200, $json['status']);
        $this->assertEquals('john@example.com', $json['data']['email']);
        $this->assertEquals(RoleEnum::Doctor->value, $json['data']['role']);
        $this->assertCount(1, $json['data']['schedules']);
        $this->assertEquals('monday', $json['data']['schedules'][0]['day_of_week']);
    }

    public function test_show_returns_404_for_nonexistent_doctor(): void
    {
        $response = $this->getJson('/api/v1/doctors/' . fake()->uuid());

        $response->assertStatus(404);
    }

    public function test_doctor_list_returns_only_doctors(): void
    {
        $this->createDoctor(['email' => 'doctor@example.com']);

        User::factory()->create([
            'role' => RoleEnum::Patient,
            'email' => 'patient@example.com',
        ]);

        $response = $this->getJson('/api/v1/doctors');

        $json = $response->json();
        $this->assertCount(1, $json['data']);
        $this->assertEquals('doctor@example.com', $json['data'][0]['email']);
    }

    public function test_doctor_list_is_paginated(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->createDoctor(['email' => "doctor{$i}@example.com"]);
        }

        $response = $this->getJson('/api/v1/doctors?limit=10');

        $json = $response->json();
        $this->assertEquals(10, $json['meta']['pagination']['limit']);
        $this->assertEquals(3, $json['meta']['pagination']['last_page']);
        $this->assertEquals(25, $json['meta']['pagination']['total']);
    }
}
