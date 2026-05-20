<?php

namespace Tests\Feature\Supervisions;

use App\Domains\Patients\Models\Patient;
use App\Enums\GenderEnum;
use App\Enums\SpecializationEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class SupervisionTest extends TestCase
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
        config(['passport.password_client_secret' => $client->plainSecret]);
    }

    private function createAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    private function createDoctor(): User
    {
        $user = User::factory()->create();
        $user->assignRole('doctor');
        $user->doctor()->create([
            'specialization' => SpecializationEnum::Cardiology,
            'experience_months' => 60,
        ]);
        return $user;
    }

    private function createPatient(): User
    {
        $user = User::factory()->create();
        $user->assignRole('patient');
        $user->patient()->create([]);
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

    private function assignPatientToDoctor(Patient $patient, User $doctorUser, User $assigner): void
    {
        $doctorUser->doctor->patients()->syncWithoutDetaching([
            $patient->id => [
                'assigned_by' => "{$assigner->id}: {$assigner->first_name} {$assigner->last_name}",
            ],
        ]);
    }

    // ─── Doctor views their patients ───

    public function test_doctor_can_view_their_patients(): void
    {
        $doctor = $this->createDoctor();
        $patient = $this->createPatient();
        $admin = $this->createAdmin();
        $this->assignPatientToDoctor($patient->patient, $doctor, $admin);

        Passport::actingAs($doctor);
        $response = $this->getJson("/api/v1/doctors/{$doctor->doctor->id}/patients");

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertCount(1, $json['data']);
        $this->assertEquals($patient->id, $json['data'][0]['id']);
        $this->assertArrayHasKey('supervision', $json['data'][0]);
        $this->assertArrayHasKey('assigned_by', $json['data'][0]['supervision']);
    }

    public function test_admin_can_view_doctor_patients(): void
    {
        $doctor = $this->createDoctor();
        $patient = $this->createPatient();
        $admin = $this->createAdmin();
        $this->assignPatientToDoctor($patient->patient, $doctor, $admin);

        Passport::actingAs($admin);
        $response = $this->getJson("/api/v1/doctors/{$doctor->doctor->id}/patients");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json()['data']);
    }

    public function test_receptionist_can_view_doctor_patients(): void
    {
        $doctor = $this->createDoctor();
        $patient = $this->createPatient();
        $admin = $this->createAdmin();
        $this->assignPatientToDoctor($patient->patient, $doctor, $admin);

        $receptionist = $this->createReceptionist();
        Passport::actingAs($receptionist);
        $response = $this->getJson("/api/v1/doctors/{$doctor->doctor->id}/patients");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json()['data']);
    }

    // ─── Authorization: viewing doctor's patients ───

    public function test_other_doctor_cannot_view_patients(): void
    {
        $doctor1 = $this->createDoctor();
        $patient = $this->createPatient();
        $admin = $this->createAdmin();
        $this->assignPatientToDoctor($patient->patient, $doctor1, $admin);

        $doctor2 = $this->createDoctor();
        Passport::actingAs($doctor2);
        $response = $this->getJson("/api/v1/doctors/{$doctor1->doctor->id}/patients");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_view_doctor_patients(): void
    {
        $doctor = $this->createDoctor();
        $response = $this->getJson("/api/v1/doctors/{$doctor->doctor->id}/patients");
        $response->assertStatus(401);
    }

    // ─── Patient views their doctors ───

    public function test_patient_can_view_their_doctors(): void
    {
        $doctor = $this->createDoctor();
        $patient = $this->createPatient();
        $admin = $this->createAdmin();
        $this->assignPatientToDoctor($patient->patient, $doctor, $admin);

        Passport::actingAs($patient);
        $response = $this->getJson("/api/v1/patients/{$patient->patient->id}/doctors");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json()['data']);
        $this->assertEquals($doctor->id, $response->json()['data'][0]['id']);
        $this->assertArrayHasKey('supervision', $response->json()['data'][0]);
        $this->assertArrayHasKey('specialization', $response->json()['data'][0]);
    }

    public function test_admin_can_view_patient_doctors(): void
    {
        $doctor = $this->createDoctor();
        $patient = $this->createPatient();
        $admin = $this->createAdmin();
        $this->assignPatientToDoctor($patient->patient, $doctor, $admin);

        Passport::actingAs($admin);
        $response = $this->getJson("/api/v1/patients/{$patient->patient->id}/doctors");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json()['data']);
    }

    public function test_other_patient_cannot_view_doctors(): void
    {
        $doctor = $this->createDoctor();
        $patient1 = $this->createPatient();
        $admin = $this->createAdmin();
        $this->assignPatientToDoctor($patient1->patient, $doctor, $admin);

        $patient2 = $this->createPatient();
        Passport::actingAs($patient2);
        $response = $this->getJson("/api/v1/patients/{$patient1->patient->id}/doctors");

        $response->assertStatus(403);
    }

    // ─── Assign patient to doctor ───

    public function test_admin_can_assign_patient_to_doctor(): void
    {
        $doctor = $this->createDoctor();
        $patient = $this->createPatient();
        $admin = $this->createAdmin();

        Passport::actingAs($admin);
        $response = $this->postJson("/api/v1/doctors/{$doctor->doctor->id}/patients", [
            'patient_id' => $patient->patient->id,
            'notes' => 'Follow up on cardiology',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('doctor_patient', [
            'doctor_id' => $doctor->doctor->id,
            'patient_id' => $patient->patient->id,
            'notes' => 'Follow up on cardiology',
        ]);
    }

    public function test_receptionist_can_assign_patient_to_doctor(): void
    {
        $doctor = $this->createDoctor();
        $patient = $this->createPatient();
        $receptionist = $this->createReceptionist();

        Passport::actingAs($receptionist);
        $response = $this->postJson("/api/v1/doctors/{$doctor->doctor->id}/patients", [
            'patient_id' => $patient->patient->id,
        ]);

        $response->assertStatus(200);
    }

    public function test_doctor_cannot_assign_patient(): void
    {
        $doctor = $this->createDoctor();
        $patient = $this->createPatient();

        Passport::actingAs($doctor);
        $response = $this->postJson("/api/v1/doctors/{$doctor->doctor->id}/patients", [
            'patient_id' => $patient->patient->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_assign_same_patient_twice_is_idempotent(): void
    {
        $doctor = $this->createDoctor();
        $patient = $this->createPatient();
        $admin = $this->createAdmin();

        Passport::actingAs($admin);
        $this->postJson("/api/v1/doctors/{$doctor->doctor->id}/patients", [
            'patient_id' => $patient->patient->id,
        ])->assertStatus(200);

        $response = $this->postJson("/api/v1/doctors/{$doctor->doctor->id}/patients", [
            'patient_id' => $patient->patient->id,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseCount('doctor_patient', 1);
    }

    public function test_assign_requires_valid_patient_id(): void
    {
        $doctor = $this->createDoctor();
        $admin = $this->createAdmin();

        Passport::actingAs($admin);
        $response = $this->postJson("/api/v1/doctors/{$doctor->doctor->id}/patients", [
            'patient_id' => 'non-existent-id',
        ]);

        $response->assertStatus(422);
    }

    // ─── Remove patient from doctor ───

    public function test_admin_can_remove_patient_from_doctor(): void
    {
        $doctor = $this->createDoctor();
        $patient = $this->createPatient();
        $admin = $this->createAdmin();
        $this->assignPatientToDoctor($patient->patient, $doctor, $admin);

        Passport::actingAs($admin);
        $response = $this->deleteJson("/api/v1/doctors/{$doctor->doctor->id}/patients/{$patient->patient->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('doctor_patient', [
            'doctor_id' => $doctor->doctor->id,
            'patient_id' => $patient->patient->id,
        ]);
    }

    public function test_non_staff_cannot_remove_patient(): void
    {
        $doctor = $this->createDoctor();
        $patient = $this->createPatient();
        $admin = $this->createAdmin();
        $this->assignPatientToDoctor($patient->patient, $doctor, $admin);

        Passport::actingAs($patient);
        $response = $this->deleteJson("/api/v1/doctors/{$doctor->doctor->id}/patients/{$patient->patient->id}");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_assign(): void
    {
        $doctor = $this->createDoctor();
        $patient = $this->createPatient();

        $response = $this->postJson("/api/v1/doctors/{$doctor->doctor->id}/patients", [
            'patient_id' => $patient->patient->id,
        ]);

        $response->assertStatus(401);
    }

    public function test_unauthenticated_cannot_remove(): void
    {
        $doctor = $this->createDoctor();
        $patient = $this->createPatient();

        $response = $this->deleteJson("/api/v1/doctors/{$doctor->doctor->id}/patients/{$patient->patient->id}");

        $response->assertStatus(401);
    }

    public function test_doctor_with_no_patients_returns_empty_list(): void
    {
        $doctor = $this->createDoctor();
        $admin = $this->createAdmin();

        Passport::actingAs($admin);
        $response = $this->getJson("/api/v1/doctors/{$doctor->doctor->id}/patients");

        $response->assertStatus(200);
        $this->assertCount(0, $response->json()['data']);
    }
}
