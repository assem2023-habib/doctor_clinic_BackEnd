<?php

namespace Tests\Feature\Doctors;

use App\Domains\Appointments\Models\Appointment;
use App\Domains\Doctors\Models\Doctor;
use App\Domains\Patients\Models\Patient;
use App\Enums\AppointmentStatusEnum;
use App\Enums\DayOfWeekEnum;
use App\Enums\GenderEnum;
use App\Enums\RoleEnum;
use App\Enums\SpecializationEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DoctorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupPassportKeys();
        $this->createPasswordGrantClient();
    }

    private function setupPassportKeys(): void
    {
        $privatePath = Passport::keyPath('oauth-private.key');
        $publicPath = Passport::keyPath('oauth-public.key');

        if (file_exists($privatePath) && file_exists($publicPath)) {
            return;
        }

        $this->artisan('passport:keys', ['--force' => true]);
    }

    private function createPasswordGrantClient(): void
    {
        $client = \Laravel\Passport\Client::create([
            'name' => 'Test Password Grant Client',
            'secret' => \Illuminate\Support\Str::random(40),
            'provider' => 'users',
            'redirect_uris' => ['http://localhost'],
            'grant_types' => ['password', 'refresh_token'],
            'personal_access_client' => false,
            'password_client' => true,
            'revoked' => false,
        ]);

        config(['passport.password_client_id' => $client->id]);
        config(['passport.password_client_secret' => $client->secret]);
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => RoleEnum::Admin,
        ]);
    }

    private function createDoctor(array $overrides = []): User
    {
        $user = User::factory()->create(array_merge([
            'role' => RoleEnum::Doctor,
        ], $overrides));

        $user->doctor()->create([
            'specialization' => SpecializationEnum::GeneralPractitioner,
            'experience_months' => 24,
        ]);

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

    public function test_admin_can_update_doctor(): void
    {
        $admin = $this->createAdmin();
        Passport::actingAs($admin);

        $doctor = $this->createDoctor([
            'first_name' => 'John',
            'email' => 'john@example.com',
        ]);

        $response = $this->putJson("/api/v1/doctors/{$doctor->doctor->id}", [
            'first_name' => 'John Updated',
            'last_name' => 'Doe',
            'username' => 'johnupdated',
            'email' => 'john.updated@example.com',
            'gender' => GenderEnum::Male->value,
            'specialization' => SpecializationEnum::Cardiology->value,
            'experience_months' => 36,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status', 'message', 'data' => ['id', 'first_name', 'last_name', 'email', 'specialization', 'experience_months'],
            ]);

        $json = $response->json();
        $this->assertEquals('John Updated', $json['data']['first_name']);
        $this->assertEquals('john.updated@example.com', $json['data']['email']);
        $this->assertEquals(SpecializationEnum::Cardiology->value, $json['data']['specialization']);
        $this->assertEquals(36, $json['data']['experience_months']);

        $this->assertDatabaseHas('users', [
            'id' => $doctor->id,
            'first_name' => 'John Updated',
            'email' => 'john.updated@example.com',
        ]);

        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->doctor->id,
            'specialization' => SpecializationEnum::Cardiology->value,
            'experience_months' => 36,
        ]);
    }

    public function test_non_admin_cannot_update_doctor(): void
    {
        $patient = User::factory()->create(['role' => RoleEnum::Patient]);
        Passport::actingAs($patient);

        $doctor = $this->createDoctor();

        $response = $this->putJson("/api/v1/doctors/{$doctor->doctor->id}", [
            'first_name' => 'Hacked',
            'last_name' => 'Name',
            'username' => 'hacked',
            'email' => 'hacked@example.com',
            'gender' => GenderEnum::Male->value,
            'specialization' => SpecializationEnum::Cardiology->value,
            'experience_months' => 12,
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_doctor_with_only_pending_appointments(): void
    {
        $admin = $this->createAdmin();
        Passport::actingAs($admin);

        $doctor = $this->createDoctor();
        $patient = User::factory()->create(['role' => RoleEnum::Patient]);
        $patient->patient()->create([]);

        $appointment = Appointment::create([
            'doctor_id' => $doctor->doctor->id,
            'patient_id' => $patient->patient->id,
            'appointment_date' => now()->addDay(),
            'start_time' => '10:00',
            'end_time' => '10:30',
            'status' => AppointmentStatusEnum::Pending,
            'created_by' => $admin->id . ': ' . $admin->first_name . ' ' . $admin->last_name,
        ]);

        $response = $this->deleteJson("/api/v1/doctors/{$doctor->doctor->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('users', ['id' => $doctor->id]);
        $this->assertDatabaseMissing('doctors', ['id' => $doctor->doctor->id]);
        $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
    }

    public function test_admin_cannot_delete_doctor_with_active_appointments(): void
    {
        $admin = $this->createAdmin();
        Passport::actingAs($admin);

        $doctor = $this->createDoctor();
        $patient = User::factory()->create(['role' => RoleEnum::Patient]);
        $patient->patient()->create([]);

        Appointment::create([
            'doctor_id' => $doctor->doctor->id,
            'patient_id' => $patient->patient->id,
            'appointment_date' => now()->addDay(),
            'start_time' => '10:00',
            'end_time' => '10:30',
            'status' => AppointmentStatusEnum::Confirmed,
            'created_by' => $admin->id . ': ' . $admin->first_name . ' ' . $admin->last_name,
        ]);

        $response = $this->deleteJson("/api/v1/doctors/{$doctor->doctor->id}");

        $response->assertStatus(409);

        $this->assertDatabaseHas('users', ['id' => $doctor->id]);
    }

    public function test_unauthenticated_user_cannot_update_doctor(): void
    {
        $doctor = $this->createDoctor();

        $response = $this->putJson("/api/v1/doctors/{$doctor->doctor->id}", [
            'first_name' => 'Hacked',
            'last_name' => 'Name',
            'username' => 'hacked',
            'email' => 'hacked@example.com',
            'gender' => GenderEnum::Male->value,
            'specialization' => SpecializationEnum::Cardiology->value,
            'experience_months' => 12,
        ]);

        $response->assertStatus(401);
    }

    public function test_admin_can_partially_update_doctor_with_patch(): void
    {
        $admin = $this->createAdmin();
        Passport::actingAs($admin);

        $doctor = $this->createDoctor([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);

        $response = $this->patchJson("/api/v1/doctors/{$doctor->doctor->id}", [
            'first_name' => 'Johnny',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'message', 'data' => ['id', 'first_name', 'last_name', 'email']]);

        $json = $response->json();
        $this->assertEquals('Johnny', $json['data']['first_name']);
        $this->assertEquals('Doe', $json['data']['last_name']);

        $this->assertDatabaseHas('users', [
            'id' => $doctor->id,
            'first_name' => 'Johnny',
            'last_name' => 'Doe',
        ]);
    }

    public function test_patch_does_not_overwrite_unsent_fields(): void
    {
        $admin = $this->createAdmin();
        Passport::actingAs($admin);

        $doctor = $this->createDoctor([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'username' => 'johndoe',
            'phone' => '+963911111111',
        ]);

        $response = $this->patchJson("/api/v1/doctors/{$doctor->doctor->id}", [
            'email' => 'john.new@example.com',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $doctor->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.new@example.com',
            'username' => 'johndoe',
            'phone' => '+963911111111',
        ]);
    }
}
