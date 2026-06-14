<?php

namespace Tests\Feature\Patients;

use App\Domains\Appointments\Models\Appointment;
use App\Domains\Doctors\Models\Specialization;
use App\Domains\Patients\Models\Patient;
use App\Enums\AppointmentStatusEnum;
use App\Enums\GenderEnum;
use App\Models\User;
use Database\Seeders\SpecializationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class PatientTest extends TestCase
{
    use RefreshDatabase;

    private Specialization $generalPractitioner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupPassportKeys();
        $this->createPasswordGrantClient();

        $this->seed(SpecializationSeeder::class);

        $this->generalPractitioner = Specialization::where('slug', 'general_practitioner')->first();
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
        config(['passport.password_client_secret' => $client->plainSecret]);
    }

    private function createAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    private function createReceptionist(): User
    {
        $user = User::factory()->create();
        $user->assignRole('receptionist');
        $user->receptionist()->create([
            'shift_start' => '09:00',
            'shift_end' => '17:00',
        ]);
        return $user;
    }

    private function createPatient(array $overrides = []): User
    {
        $user = User::factory()->create($overrides);
        $user->assignRole('patient');
        $user->patient()->create([]);

        return $user;
    }

    public function test_staff_can_list_patients(): void
    {
        $admin = $this->createAdmin();
        Passport::actingAs($admin);

        $this->createPatient(['first_name' => 'John', 'email' => 'john@example.com']);
        $this->createPatient(['first_name' => 'Jane', 'email' => 'jane@example.com']);

        $response = $this->getJson('/api/v1/patients');

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

    public function test_receptionist_can_list_patients(): void
    {
        $receptionist = $this->createReceptionist();
        Passport::actingAs($receptionist);

        $this->createPatient(['email' => 'john@example.com']);

        $response = $this->getJson('/api/v1/patients');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json()['data']);
    }

    public function test_unauthorized_user_cannot_list_patients(): void
    {
        $patient = $this->createPatient();
        Passport::actingAs($patient);

        $response = $this->getJson('/api/v1/patients');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_list_patients(): void
    {
        $response = $this->getJson('/api/v1/patients');

        $response->assertStatus(401);
    }

    public function test_staff_can_show_single_patient(): void
    {
        $admin = $this->createAdmin();
        Passport::actingAs($admin);

        $patientUser = $this->createPatient([
            'first_name' => 'John',
            'email' => 'john@example.com',
        ]);

        $response = $this->getJson("/api/v1/patients/{$patientUser->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['id', 'first_name', 'last_name', 'email', 'roles'],
            ]);

        $json = $response->json();
        $this->assertEquals(200, $json['status']);
        $this->assertEquals('john@example.com', $json['data']['email']);
        $this->assertEquals('Patient', $json['data']['roles'][0]);
    }

    public function test_show_returns_404_for_nonexistent_patient(): void
    {
        $admin = $this->createAdmin();
        Passport::actingAs($admin);

        $response = $this->getJson('/api/v1/patients/' . fake()->uuid());

        $response->assertStatus(404);
    }

    public function test_patient_list_returns_only_patients(): void
    {
        $admin = $this->createAdmin();
        Passport::actingAs($admin);

        $this->createPatient(['email' => 'patient@example.com']);

        $doctorUser = User::factory()->create(['email' => 'doctor@example.com']);
        $doctorUser->assignRole('doctor');

        $response = $this->getJson('/api/v1/patients');

        $json = $response->json();
        $this->assertCount(1, $json['data']);
        $this->assertEquals('patient@example.com', $json['data'][0]['email']);
    }

    public function test_admin_can_update_patient(): void
    {
        $admin = $this->createAdmin();
        Passport::actingAs($admin);

        $patientUser = $this->createPatient([
            'first_name' => 'John',
            'email' => 'john@example.com',
        ]);

        $response = $this->putJson("/api/v1/patients/{$patientUser->id}", [
            'first_name' => 'John Updated',
            'last_name' => 'Doe',
            'username' => 'johnupdated',
            'email' => 'john.updated@example.com',
            'gender' => GenderEnum::Male->value,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status', 'message', 'data' => ['id', 'first_name', 'last_name', 'email'],
            ]);

        $json = $response->json();
        $this->assertEquals('John Updated', $json['data']['first_name']);
        $this->assertEquals('john.updated@example.com', $json['data']['email']);

        $this->assertDatabaseHas('users', [
            'id' => $patientUser->id,
            'first_name' => 'John Updated',
            'email' => 'john.updated@example.com',
        ]);
    }

    public function test_non_admin_cannot_update_patient(): void
    {
        $patient = $this->createPatient();
        Passport::actingAs($patient);

        $targetPatient = $this->createPatient();

        $response = $this->putJson("/api/v1/patients/{$targetPatient->id}", [
            'first_name' => 'Hacked',
            'last_name' => 'Name',
            'username' => 'hacked',
            'email' => 'hacked@example.com',
            'gender' => GenderEnum::Male->value,
        ]);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_update_patient(): void
    {
        $patient = $this->createPatient();

        $response = $this->putJson("/api/v1/patients/{$patient->id}", [
            'first_name' => 'Hacked',
            'last_name' => 'Name',
            'username' => 'hacked',
            'email' => 'hacked@example.com',
            'gender' => GenderEnum::Male->value,
        ]);

        $response->assertStatus(401);
    }

    public function test_admin_can_partially_update_patient_with_patch(): void
    {
        $admin = $this->createAdmin();
        Passport::actingAs($admin);

        $patientUser = $this->createPatient([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);

        $response = $this->patchJson("/api/v1/patients/{$patientUser->id}", [
            'first_name' => 'Johnny',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'message', 'data' => ['id', 'first_name', 'last_name', 'email']]);

        $json = $response->json();
        $this->assertEquals('Johnny', $json['data']['first_name']);
        $this->assertEquals('Doe', $json['data']['last_name']);

        $this->assertDatabaseHas('users', [
            'id' => $patientUser->id,
            'first_name' => 'Johnny',
            'last_name' => 'Doe',
        ]);
    }

    public function test_patch_does_not_overwrite_unsent_fields(): void
    {
        $admin = $this->createAdmin();
        Passport::actingAs($admin);

        $patientUser = $this->createPatient([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'username' => 'johndoe',
            'phone' => '+963911111111',
        ]);

        $response = $this->patchJson("/api/v1/patients/{$patientUser->id}", [
            'email' => 'john.new@example.com',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $patientUser->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.new@example.com',
            'username' => 'johndoe',
            'phone' => '+963911111111',
        ]);
    }

    public function test_admin_can_delete_patient_with_no_active_appointments(): void
    {
        $admin = $this->createAdmin();
        Passport::actingAs($admin);

        $patientUser = $this->createPatient();
        $patientId = $patientUser->patient->id;

        $response = $this->deleteJson("/api/v1/patients/{$patientUser->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('users', ['id' => $patientUser->id]);
        $this->assertDatabaseMissing('patients', ['id' => $patientId]);
    }

    public function test_admin_cannot_delete_patient_with_active_appointments(): void
    {
        $admin = $this->createAdmin();
        Passport::actingAs($admin);

        $patientUser = $this->createPatient();
        $doctorUser = User::factory()->create();
        $doctorUser->assignRole('doctor');
        $doctorUser->doctor()->create([
            'specialization_id' => $this->generalPractitioner->id,
        ]);

        Appointment::create([
            'doctor_id' => $doctorUser->doctor->id,
            'patient_id' => $patientUser->patient->id,
            'appointment_date' => now()->addDay(),
            'start_time' => '10:00',
            'end_time' => '10:30',
            'status' => AppointmentStatusEnum::Confirmed,
            'created_by' => $admin->id . ': ' . $admin->first_name . ' ' . $admin->last_name,
        ]);

        $response = $this->deleteJson("/api/v1/patients/{$patientUser->id}");

        $response->assertStatus(409);

        $this->assertDatabaseHas('users', ['id' => $patientUser->id]);
    }

    public function test_unauthenticated_user_cannot_delete_patient(): void
    {
        $patientUser = $this->createPatient();

        $response = $this->deleteJson("/api/v1/patients/{$patientUser->id}");

        $response->assertStatus(401);
    }

    public function test_admin_can_create_patient(): void
    {
        $admin = $this->createAdmin();
        Passport::actingAs($admin);

        $response = $this->postJson('/api/v1/patients', [
            'first_name' => 'New',
            'last_name' => 'Patient',
            'username' => 'newpatient',
            'email' => 'newpatient@example.com',
            'gender' => GenderEnum::Male->value,
            'password' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status', 'message',
                'data' => ['id', 'first_name', 'last_name', 'email', 'is_active'],
            ]);

        $json = $response->json();
        $this->assertEquals('New', $json['data']['first_name']);
        $this->assertEquals('newpatient@example.com', $json['data']['email']);
        $this->assertTrue($json['data']['is_active']);

        $this->assertDatabaseHas('users', ['email' => 'newpatient@example.com', 'is_active' => true]);
        $this->assertDatabaseHas('patients', ['user_id' => User::where('email', 'newpatient@example.com')->first()->id]);
    }

    public function test_non_admin_cannot_create_patient(): void
    {
        $patient = $this->createPatient();
        Passport::actingAs($patient);

        $response = $this->postJson('/api/v1/patients', [
            'first_name' => 'Hacked',
            'last_name' => 'Patient',
            'username' => 'hackedpatient',
            'email' => 'hacked@example.com',
            'gender' => GenderEnum::Male->value,
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_create_patient(): void
    {
        $response = $this->postJson('/api/v1/patients', [
            'first_name' => 'Hacked',
            'last_name' => 'Patient',
            'username' => 'hackedpatient',
            'email' => 'hacked@example.com',
            'gender' => GenderEnum::Male->value,
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    public function test_doctor_can_list_patients(): void
    {
        $doctorUser = User::factory()->create();
        $doctorUser->assignRole('doctor');
        $doctorUser->doctor()->create([
            'specialization_id' => $this->generalPractitioner->id,
        ]);
        Passport::actingAs($doctorUser);

        $this->createPatient(['email' => 'patient1@example.com']);
        $this->createPatient(['email' => 'patient2@example.com']);

        $response = $this->getJson('/api/v1/patients');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json()['data']);
    }

    public function test_doctor_can_show_single_patient(): void
    {
        $doctorUser = User::factory()->create();
        $doctorUser->assignRole('doctor');
        $doctorUser->doctor()->create([
            'specialization_id' => $this->generalPractitioner->id,
        ]);
        Passport::actingAs($doctorUser);

        $patientUser = $this->createPatient(['email' => 'patient@example.com']);

        $response = $this->getJson("/api/v1/patients/{$patientUser->id}");

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertEquals('patient@example.com', $json['data']['email']);
    }

    public function test_list_patients_can_filter_by_gender(): void
    {
        $admin = $this->createAdmin();
        Passport::actingAs($admin);

        $this->createPatient(['first_name' => 'Male', 'gender' => GenderEnum::Male->value]);
        $this->createPatient(['first_name' => 'Female', 'gender' => GenderEnum::Female->value]);

        $response = $this->getJson('/api/v1/patients?gender=' . GenderEnum::Male->value);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json()['data']);
        $this->assertEquals('Male', $response->json()['data'][0]['first_name']);
    }

    public function test_list_patients_can_filter_by_date_range(): void
    {
        $admin = $this->createAdmin();
        Passport::actingAs($admin);

        $this->createPatient(['first_name' => 'Old', 'birthday_date' => '1990-01-01']);
        $this->createPatient(['first_name' => 'Young', 'birthday_date' => '2000-01-01']);

        $response = $this->getJson('/api/v1/patients?date_from=1995-01-01&date_to=2005-01-01');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json()['data']);
        $this->assertEquals('Young', $response->json()['data'][0]['first_name']);
    }

    public function test_list_patients_can_filter_by_is_active(): void
    {
        $admin = $this->createAdmin();
        Passport::actingAs($admin);

        $this->createPatient(['first_name' => 'Active', 'is_active' => true]);
        $this->createPatient(['first_name' => 'Inactive', 'is_active' => false]);

        $response = $this->getJson('/api/v1/patients?is_active=1');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json()['data']);
        $this->assertEquals('Active', $response->json()['data'][0]['first_name']);
    }
}
