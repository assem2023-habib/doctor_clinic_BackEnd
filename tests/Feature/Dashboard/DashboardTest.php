<?php

namespace Tests\Feature\Dashboard;

use App\Domains\Appointments\Models\Appointment;
use App\Domains\Doctors\Models\Specialization;
use App\Domains\MedicalRecords\Models\MedicalRecord;
use App\Domains\Prescriptions\Models\Prescription;
use App\Enums\AppointmentStatusEnum;
use App\Enums\DayOfWeekEnum;
use App\Enums\GenderEnum;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\SpecializationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    private function createDoctorUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('doctor');
        $user->doctor()->create([
            'specialization_id' => $this->generalPractitioner->id,
            'experience_months' => 24,
        ]);
        return $user;
    }

    private function createPatientUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('patient');
        $user->patient()->create([]);
        return $user;
    }

    private function createAdminUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    private function createReceptionistUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('receptionist');
        return $user;
    }

    public function test_admin_dashboard_has_all_sections(): void
    {
        $admin = $this->createAdminUser();
        Passport::actingAs($admin);

        $doctor = $this->createDoctorUser();
        $patient = $this->createPatientUser();

        $doctor->doctor->patients()->attach($patient->patient->id, [
            'supervision_status' => 'active',
            'assigned_by' => $admin->id,
        ]);

        Appointment::create([
            'doctor_id' => $doctor->doctor->id,
            'patient_id' => $patient->patient->id,
            'appointment_date' => now()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '10:30',
            'status' => AppointmentStatusEnum::Set,
            'reason' => 'Checkup',
            'created_by' => $patient->id,
        ]);

        MedicalRecord::create([
            'patient_id' => $patient->patient->id,
            'doctor_id' => $doctor->doctor->id,
            'diagnosis' => 'Test diagnosis',
            'notes' => 'Test notes',
        ]);

        $record = MedicalRecord::first();
        Prescription::create([
            'medical_record_id' => $record->id,
            'prescription_date' => now()->format('Y-m-d'),
            'status' => 'active',
            'notes' => 'Take medicine',
        ]);

        $rater = $this->createPatientUser();
        \App\Domains\Ratings\Models\Rating::create([
            'rater_id' => $rater->id,
            'type' => 'user',
            'rateable_id' => $doctor->id,
            'rateable_type' => 'App\Models\User',
            'rating' => 5,
            'comment' => 'Great doctor',
        ]);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(200, $json['status']);
        $this->assertArrayHasKey('users', $json['data']);
        $this->assertArrayHasKey('appointments', $json['data']);
        $this->assertArrayHasKey('medical_records', $json['data']);
        $this->assertArrayHasKey('prescriptions', $json['data']);
        $this->assertArrayHasKey('specializations', $json['data']);
        $this->assertArrayHasKey('ratings', $json['data']);

        $ratings = $json['data']['ratings'];
        $this->assertArrayHasKey('average', $ratings);
        $this->assertArrayHasKey('total', $ratings);
        $this->assertArrayHasKey('negative_count', $ratings);
        $this->assertArrayHasKey('top_positive', $ratings);
        $this->assertArrayHasKey('lowest_positive', $ratings);
        $this->assertArrayHasKey('most_rated', $ratings);
        $this->assertArrayHasKey('top_per_specialization', $ratings);

        $this->assertEquals(5.0, $ratings['average']);
        $this->assertEquals(1, $ratings['total']);
        $this->assertEquals(0, $ratings['negative_count']);
    }

    public function test_doctor_dashboard_includes_negative_count(): void
    {
        $doctor = $this->createDoctorUser();
        Passport::actingAs($doctor);

        $rater1 = $this->createPatientUser();
        $rater2 = $this->createPatientUser();

        \App\Domains\Ratings\Models\Rating::create([
            'rater_id' => $rater1->id,
            'type' => 'user',
            'rateable_id' => $doctor->id,
            'rateable_type' => 'App\Models\User',
            'rating' => 5,
        ]);

        \App\Domains\Ratings\Models\Rating::create([
            'rater_id' => $rater2->id,
            'type' => 'user',
            'rateable_id' => $doctor->id,
            'rateable_type' => 'App\Models\User',
            'rating' => 1,
        ]);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertArrayHasKey('ratings', $json['data']);
        $ratings = $json['data']['ratings'];
        $this->assertArrayHasKey('negative_count', $ratings);
        $this->assertEquals(1, $ratings['negative_count']);
        $this->assertEquals(3.0, $ratings['average']);
        $this->assertEquals(2, $ratings['total']);
    }

    public function test_receptionist_dashboard_includes_ratings_and_counts(): void
    {
        $admin = $this->createAdminUser();
        $receptionist = $this->createReceptionistUser();
        Passport::actingAs($receptionist);

        $doctor = $this->createDoctorUser();
        $patient = $this->createPatientUser();

        MedicalRecord::create([
            'patient_id' => $patient->patient->id,
            'doctor_id' => $doctor->doctor->id,
            'diagnosis' => 'Test',
        ]);

        $record = MedicalRecord::first();
        Prescription::create([
            'medical_record_id' => $record->id,
            'prescription_date' => now()->format('Y-m-d'),
            'status' => 'active',
        ]);

        $rater = $this->createPatientUser();
        \App\Domains\Ratings\Models\Rating::create([
            'rater_id' => $rater->id,
            'type' => 'user',
            'rateable_id' => $doctor->id,
            'rateable_type' => 'App\Models\User',
            'rating' => 4,
        ]);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertArrayHasKey('medical_records', $json['data']);
        $this->assertArrayHasKey('prescriptions', $json['data']);
        $this->assertArrayHasKey('ratings', $json['data']);
        $this->assertArrayHasKey('appointments', $json['data']);
        $this->assertArrayHasKey('patients', $json['data']);
        $this->assertArrayHasKey('doctors', $json['data']);

        $ratings = $json['data']['ratings'];
        $this->assertArrayHasKey('average', $ratings);
        $this->assertArrayHasKey('total', $ratings);
        $this->assertArrayHasKey('negative_count', $ratings);
        $this->assertArrayHasKey('top_positive', $ratings);
        $this->assertArrayHasKey('lowest_positive', $ratings);
        $this->assertArrayHasKey('most_rated', $ratings);
        $this->assertArrayHasKey('top_per_specialization', $ratings);

        $this->assertEquals(1, $json['data']['medical_records']['total']);
        $this->assertEquals(1, $json['data']['prescriptions']['total']);
    }

    public function test_patient_dashboard_has_medical_records_and_prescriptions(): void
    {
        $patient = $this->createPatientUser();
        Passport::actingAs($patient);

        $doctor = $this->createDoctorUser();

        MedicalRecord::create([
            'patient_id' => $patient->patient->id,
            'doctor_id' => $doctor->doctor->id,
            'diagnosis' => 'Test',
        ]);

        $record = MedicalRecord::first();
        Prescription::create([
            'medical_record_id' => $record->id,
            'prescription_date' => now()->format('Y-m-d'),
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertArrayHasKey('medical_records', $json['data']);
        $this->assertArrayHasKey('prescriptions', $json['data']);
        $this->assertArrayHasKey('doctors', $json['data']);
        $this->assertArrayHasKey('appointments', $json['data']);

        $this->assertEquals(1, $json['data']['medical_records']['total']);
        $this->assertEquals(1, $json['data']['prescriptions']['total']);
    }

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->getJson('/api/v1/dashboard');
        $response->assertStatus(401);
    }
}
