<?php

namespace Tests\Feature\Doctors;

use App\Domains\Appointments\Models\Appointment;
use App\Domains\Doctors\Models\Doctor;
use App\Domains\Doctors\Models\Specialization;
use App\Domains\Patients\Models\Patient;
use App\Domains\Ratings\Models\Rating;
use App\Enums\AppointmentStatusEnum;
use App\Enums\DayOfWeekEnum;
use App\Enums\GenderEnum;
use App\Enums\RatingTypeEnum;
use App\Models\User;
use Database\Seeders\SpecializationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DoctorTest extends TestCase
{
    use RefreshDatabase;

    private Specialization $generalPractitioner;
    private Specialization $cardiology;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupPassportKeys();
        $this->createPasswordGrantClient();

        $this->seed(SpecializationSeeder::class);

        $this->generalPractitioner = Specialization::where('slug', 'general_practitioner')->first();
        $this->cardiology = Specialization::where('slug', 'cardiology')->first();
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

    private function createDoctor(array $overrides = []): User
    {
        $user = User::factory()->create($overrides);
        $user->assignRole('doctor');
        $user->doctor()->create([
            'specialization_id' => $this->generalPractitioner->id,
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

        Passport::actingAs($this->createAdmin());
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

        Passport::actingAs($this->createAdmin());
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

        Passport::actingAs($this->createAdmin());
        $response = $this->getJson("/api/v1/doctors/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['id', 'first_name', 'last_name', 'email', 'roles', 'schedules'],
            ]);

        $json = $response->json();
        $this->assertEquals(200, $json['status']);
        $this->assertEquals('john@example.com', $json['data']['email']);
        $this->assertEquals('Doctor', $json['data']['roles'][0]);
        $this->assertCount(1, $json['data']['schedules']);
        $this->assertEquals('monday', $json['data']['schedules'][0]['day_of_week']);
    }

    public function test_show_returns_404_for_nonexistent_doctor(): void
    {
        Passport::actingAs($this->createAdmin());
        $response = $this->getJson('/api/v1/doctors/' . fake()->uuid());

        $response->assertStatus(404);
    }

    public function test_doctor_list_returns_only_doctors(): void
    {
        $this->createDoctor(['email' => 'doctor@example.com']);

        $patientUser = User::factory()->create(['email' => 'patient@example.com']);
        $patientUser->assignRole('patient');

        Passport::actingAs($this->createAdmin());
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

        Passport::actingAs($this->createAdmin());
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

        $response = $this->putJson("/api/v1/doctors/{$doctor->id}", [
            'first_name' => 'John Updated',
            'last_name' => 'Doe',
            'username' => 'johnupdated',
            'email' => 'john.updated@example.com',
            'gender' => GenderEnum::Male->value,
            'specialization_id' => $this->cardiology->id,
            'experience_months' => 36,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status', 'message', 'data' => ['id', 'first_name', 'last_name', 'email', 'specialization', 'experience_months'],
            ]);

        $json = $response->json();
        $this->assertEquals('John Updated', $json['data']['first_name']);
        $this->assertEquals('john.updated@example.com', $json['data']['email']);
        $this->assertEquals('cardiology', $json['data']['specialization']['slug']);
        $this->assertEquals(36, $json['data']['experience_months']);

        $this->assertDatabaseHas('users', [
            'id' => $doctor->id,
            'first_name' => 'John Updated',
            'email' => 'john.updated@example.com',
        ]);

        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->doctor->id,
            'specialization_id' => $this->cardiology->id,
            'experience_months' => 36,
        ]);
    }

    public function test_non_admin_cannot_update_doctor(): void
    {
        $patient = User::factory()->create();
        $patient->assignRole('patient');
        Passport::actingAs($patient);

        $doctor = $this->createDoctor();

        $response = $this->putJson("/api/v1/doctors/{$doctor->id}", [
            'first_name' => 'Hacked',
            'last_name' => 'Name',
            'username' => 'hacked',
            'email' => 'hacked@example.com',
            'gender' => GenderEnum::Male->value,
            'specialization_id' => $this->cardiology->id,
            'experience_months' => 12,
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_doctor_with_only_pending_appointments(): void
    {
        $admin = $this->createAdmin();
        Passport::actingAs($admin);

        $doctor = $this->createDoctor();
        $patient = User::factory()->create();
        $patient->assignRole('patient');
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

        $response = $this->deleteJson("/api/v1/doctors/{$doctor->id}");

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
        $patient = User::factory()->create();
        $patient->assignRole('patient');
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

        $response = $this->deleteJson("/api/v1/doctors/{$doctor->id}");

        $response->assertStatus(409);

        $this->assertDatabaseHas('users', ['id' => $doctor->id]);
    }

    public function test_admin_can_activate_doctor_account(): void
    {
        $admin = $this->createAdmin();
        Passport::actingAs($admin);

        $user = User::factory()->create(['is_active' => false]);
        $user->assignRole('doctor');
        $user->doctor()->create([
            'specialization_id' => $this->generalPractitioner->id,
            'experience_months' => 24,
        ]);

        $response = $this->putJson("/api/v1/doctors/{$user->id}/activate-account");

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'message', 'data' => ['id', 'first_name', 'last_name', 'email', 'is_active']]);

        $json = $response->json();
        $this->assertEquals(__('auth.account_activated'), $json['message']);
        $this->assertTrue($json['data']['is_active']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => true,
        ]);
    }

    public function test_non_admin_cannot_activate_doctor_account(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $user->assignRole('doctor');
        $user->doctor()->create([
            'specialization_id' => $this->generalPractitioner->id,
            'experience_months' => 24,
        ]);
        Passport::actingAs($user);

        $response = $this->putJson("/api/v1/doctors/{$user->id}/activate-account");

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => false,
        ]);
    }

    public function test_unauthenticated_user_cannot_update_doctor(): void
    {
        $doctor = $this->createDoctor();

        $response = $this->putJson("/api/v1/doctors/{$doctor->id}", [
            'first_name' => 'Hacked',
            'last_name' => 'Name',
            'username' => 'hacked',
            'email' => 'hacked@example.com',
            'gender' => GenderEnum::Male->value,
            'specialization_id' => $this->cardiology->id,
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

        $response = $this->patchJson("/api/v1/doctors/{$doctor->id}", [
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

        $response = $this->patchJson("/api/v1/doctors/{$doctor->id}", [
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

    public function test_show_returns_rating_key_with_empty_values(): void
    {
        $user = $this->createDoctor();
        $this->createSchedule($user->doctor);

        Passport::actingAs($this->createAdmin());
        $response = $this->getJson("/api/v1/doctors/{$user->id}");

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertArrayHasKey('rating', $json['data']);
        $this->assertEquals(0, $json['data']['rating']['avg']);
        $this->assertEquals(0, $json['data']['rating']['count']);
        $this->assertEmpty($json['data']['rating']['recent']);
    }

    public function test_show_includes_rating_data_with_avg_and_recent(): void
    {
        $user = $this->createDoctor();
        $this->createSchedule($user->doctor);

        $ratings = [];
        for ($i = 0; $i < 7; $i++) {
            $rater = User::factory()->create();
            $rater->assignRole('patient');
            $ratings[] = Rating::create([
                'rater_id' => $rater->id,
                'type' => RatingTypeEnum::User,
                'rateable_id' => $user->id,
                'rateable_type' => 'App\Models\User',
                'rating' => ($i % 5) + 1,
                'comment' => "Rating {$i}",
                'created_at' => now()->subDays(7 - $i),
            ]);
        }

        Passport::actingAs($this->createAdmin());
        $response = $this->getJson("/api/v1/doctors/{$user->id}");

        $response->assertStatus(200);
        $json = $response->json();

        $expectedAvg = round(array_sum(array_column($ratings, 'rating')) / count($ratings), 1);
        $this->assertEquals($expectedAvg, $json['data']['rating']['avg']);
        $this->assertEquals(7, $json['data']['rating']['count']);
        $this->assertCount(5, $json['data']['rating']['recent']);

        $recent = $json['data']['rating']['recent'];
        $recentDates = array_map(fn ($r) => $r['created_at'], $recent);
        $sortedDates = $recentDates;
        rsort($sortedDates);
        $this->assertEquals($sortedDates, $recentDates, 'Recent ratings must be ordered by created_at desc');

        $firstRecent = $json['data']['rating']['recent'][0];
        $this->assertArrayHasKey('id', $firstRecent);
        $this->assertArrayHasKey('rating', $firstRecent);
        $this->assertArrayHasKey('comment', $firstRecent);
        $this->assertArrayHasKey('rater', $firstRecent);
        $this->assertArrayHasKey('first_name', $firstRecent['rater']);
        $this->assertArrayHasKey('last_name', $firstRecent['rater']);
        $this->assertArrayHasKey('created_at', $firstRecent);
    }

    public function test_doctor_ratings_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/doctors/' . fake()->uuid() . '/ratings');

        $response->assertStatus(401);
    }

    public function test_doctor_ratings_returns_paginated_ratings(): void
    {
        $doctor = $this->createDoctor();
        for ($i = 0; $i < 15; $i++) {
            $rater = User::factory()->create();
            $rater->assignRole('patient');
            Rating::create([
                'rater_id' => $rater->id,
                'type' => RatingTypeEnum::User,
                'rateable_id' => $doctor->id,
                'rateable_type' => 'App\Models\User',
                'rating' => ($i % 5) + 1,
                'comment' => "Test rating {$i}",
            ]);
        }

        Passport::actingAs($this->createAdmin());
        $response = $this->getJson("/api/v1/doctors/{$doctor->id}/ratings?limit=5");

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertCount(5, $json['data']);
        $this->assertArrayHasKey('meta', $json);
        $this->assertArrayHasKey('pagination', $json['meta']);
        $this->assertEquals(5, $json['meta']['pagination']['limit']);
        $this->assertEquals(3, $json['meta']['pagination']['last_page']);
        $this->assertEquals(15, $json['meta']['pagination']['total']);

        $firstRating = $json['data'][0];
        $this->assertArrayHasKey('id', $firstRating);
        $this->assertArrayHasKey('rating', $firstRating);
        $this->assertArrayHasKey('comment', $firstRating);
        $this->assertArrayHasKey('rater', $firstRating);
        $this->assertArrayHasKey('type', $firstRating);
    }

    public function test_doctor_ratings_returns_404_for_nonexistent_doctor(): void
    {
        Passport::actingAs($this->createAdmin());
        $response = $this->getJson('/api/v1/doctors/' . fake()->uuid() . '/ratings');

        $response->assertStatus(404);
    }

    public function test_doctor_ratings_search_filters_by_comment_or_rater_name(): void
    {
        $doctor = $this->createDoctor();

        $raterJohn = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
        $raterJohn->assignRole('patient');
        $raterJane = User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith']);
        $raterJane->assignRole('patient');
        $raterBob = User::factory()->create(['first_name' => 'Bob', 'last_name' => 'Brown']);
        $raterBob->assignRole('patient');

        Rating::create([
            'rater_id' => $raterJohn->id,
            'type' => RatingTypeEnum::User,
            'rateable_id' => $doctor->id,
            'rateable_type' => 'App\Models\User',
            'rating' => 5,
            'comment' => 'Excellent doctor, very professional',
        ]);

        Rating::create([
            'rater_id' => $raterJane->id,
            'type' => RatingTypeEnum::User,
            'rateable_id' => $doctor->id,
            'rateable_type' => 'App\Models\User',
            'rating' => 3,
            'comment' => 'Average experience',
        ]);

        Rating::create([
            'rater_id' => $raterBob->id,
            'type' => RatingTypeEnum::User,
            'rateable_id' => $doctor->id,
            'rateable_type' => 'App\Models\User',
            'rating' => 4,
            'comment' => 'Good follow-up care',
        ]);

        Passport::actingAs($this->createAdmin());

        $responseSearchJohn = $this->getJson("/api/v1/doctors/{$doctor->id}/ratings?search=John");
        $responseSearchJohn->assertStatus(200);
        $this->assertCount(1, $responseSearchJohn->json()['data']);

        $responseSearchExcellent = $this->getJson("/api/v1/doctors/{$doctor->id}/ratings?search=Excellent");
        $responseSearchExcellent->assertStatus(200);
        $this->assertCount(1, $responseSearchExcellent->json()['data']);

        $responseSearchNoMatch = $this->getJson("/api/v1/doctors/{$doctor->id}/ratings?search=zzzzz");
        $responseSearchNoMatch->assertStatus(200);
        $this->assertCount(0, $responseSearchNoMatch->json()['data']);
    }

    public function test_doctor_list_filters_by_has_appointments(): void
    {
        $doctorWithAppointments = $this->createDoctor();
        $doctorWithoutAppointments = $this->createDoctor();
        $patient = User::factory()->create();
        $patient->assignRole('patient');
        $patient->patient()->create([]);
        Appointment::create([
            'doctor_id' => $doctorWithAppointments->doctor->id,
            'patient_id' => $patient->patient->id,
            'reason' => 'Test',
            'status' => AppointmentStatusEnum::Requested,
            'created_by' => $patient->id,
        ]);

        Passport::actingAs($this->createAdmin());
        $response = $this->getJson('/api/v1/doctors?has_appointments=true');

        $response->assertStatus(200);
        $ids = collect($response->json()['data'])->pluck('id');
        $this->assertContains($doctorWithAppointments->id, $ids);
        $this->assertNotContains($doctorWithoutAppointments->id, $ids);
    }

    public function test_doctor_list_filters_by_appointment_status(): void
    {
        $doctorAccepted = $this->createDoctor();
        $doctorRequested = $this->createDoctor();
        $patient = User::factory()->create();
        $patient->assignRole('patient');
        $patient->patient()->create([]);
        Appointment::create([
            'doctor_id' => $doctorAccepted->doctor->id,
            'patient_id' => $patient->patient->id,
            'status' => AppointmentStatusEnum::Accepted,
            'created_by' => $patient->id,
        ]);
        Appointment::create([
            'doctor_id' => $doctorRequested->doctor->id,
            'patient_id' => $patient->patient->id,
            'status' => AppointmentStatusEnum::Requested,
            'created_by' => $patient->id,
        ]);

        Passport::actingAs($this->createAdmin());
        $response = $this->getJson('/api/v1/doctors?appointment_status[]=' . AppointmentStatusEnum::Accepted->value);

        $response->assertStatus(200);
        $ids = collect($response->json()['data'])->pluck('id');
        $this->assertContains($doctorAccepted->id, $ids);
        $this->assertNotContains($doctorRequested->id, $ids);
    }

    public function test_doctor_list_filters_by_appointment_date(): void
    {
        $doctorOnDate = $this->createDoctor();
        $doctorOtherDate = $this->createDoctor();
        $patient = User::factory()->create();
        $patient->assignRole('patient');
        $patient->patient()->create([]);
        Appointment::create([
            'doctor_id' => $doctorOnDate->doctor->id,
            'patient_id' => $patient->patient->id,
            'appointment_date' => '2026-06-15',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => AppointmentStatusEnum::Accepted,
            'created_by' => $patient->id,
        ]);
        Appointment::create([
            'doctor_id' => $doctorOtherDate->doctor->id,
            'patient_id' => $patient->patient->id,
            'appointment_date' => '2026-07-01',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => AppointmentStatusEnum::Accepted,
            'created_by' => $patient->id,
        ]);

        Passport::actingAs($this->createAdmin());
        $response = $this->getJson('/api/v1/doctors?appointment_date=2026-06-15');

        $response->assertStatus(200);
        $ids = collect($response->json()['data'])->pluck('id');
        $this->assertContains($doctorOnDate->id, $ids);
        $this->assertNotContains($doctorOtherDate->id, $ids);
    }

    public function test_doctor_list_filters_by_appointment_date_range(): void
    {
        $doctorInRange = $this->createDoctor();
        $doctorOutOfRange = $this->createDoctor();
        $patient = User::factory()->create();
        $patient->assignRole('patient');
        $patient->patient()->create([]);
        Appointment::create([
            'doctor_id' => $doctorInRange->doctor->id,
            'patient_id' => $patient->patient->id,
            'appointment_date' => '2026-06-15',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => AppointmentStatusEnum::Accepted,
            'created_by' => $patient->id,
        ]);
        Appointment::create([
            'doctor_id' => $doctorOutOfRange->doctor->id,
            'patient_id' => $patient->patient->id,
            'appointment_date' => '2026-07-15',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => AppointmentStatusEnum::Accepted,
            'created_by' => $patient->id,
        ]);

        Passport::actingAs($this->createAdmin());
        $response = $this->getJson('/api/v1/doctors?appointment_from_date=2026-06-01&appointment_to_date=2026-06-30');

        $response->assertStatus(200);
        $ids = collect($response->json()['data'])->pluck('id');
        $this->assertContains($doctorInRange->id, $ids);
        $this->assertNotContains($doctorOutOfRange->id, $ids);
    }

    public function test_doctor_list_filters_by_appointment_patient_id(): void
    {
        $doctor = $this->createDoctor();
        $otherDoctor = $this->createDoctor();
        $patient = User::factory()->create();
        $patient->assignRole('patient');
        $patient->patient()->create([]);
        Appointment::create([
            'doctor_id' => $doctor->doctor->id,
            'patient_id' => $patient->patient->id,
            'status' => AppointmentStatusEnum::Accepted,
            'created_by' => $patient->id,
        ]);

        Passport::actingAs($this->createAdmin());
        $response = $this->getJson('/api/v1/doctors?appointment_patient_id[]=' . $patient->id);

        $response->assertStatus(200);
        $ids = collect($response->json()['data'])->pluck('id');
        $this->assertContains($doctor->id, $ids);
        $this->assertNotContains($otherDoctor->id, $ids);
    }

    public function test_doctor_list_has_appointments_false_returns_doctors_without_appointments(): void
    {
        $doctorWithAppointments = $this->createDoctor();
        $doctorWithoutAppointments = $this->createDoctor();
        $patient = User::factory()->create();
        $patient->assignRole('patient');
        $patient->patient()->create([]);
        Appointment::create([
            'doctor_id' => $doctorWithAppointments->doctor->id,
            'patient_id' => $patient->patient->id,
            'status' => AppointmentStatusEnum::Requested,
            'created_by' => $patient->id,
        ]);

        Passport::actingAs($this->createAdmin());
        $response = $this->getJson('/api/v1/doctors?has_appointments=false');

        $response->assertStatus(200);
        $ids = collect($response->json()['data'])->pluck('id');
        $this->assertContains($doctorWithoutAppointments->id, $ids);
        $this->assertNotContains($doctorWithAppointments->id, $ids);
    }

    public function test_doctor_list_cache_hit_returns_stale_data_until_update(): void
    {
        $doctor = $this->createDoctor();
        Passport::actingAs($this->createAdmin());
        Cache::forget('doctors:cache_version');

        $response1 = $this->getJson('/api/v1/doctors?limit=50');
        $firstName1 = $response1->json('data')[0]['first_name'];

        $doctor->first_name = 'StaleName';
        $doctor->saveQuietly();

        $response2 = $this->getJson('/api/v1/doctors?limit=50');
        $this->assertEquals($firstName1, $response2->json('data')[0]['first_name']);
    }

    public function test_doctor_list_cache_invalidated_after_update(): void
    {
        $doctor = $this->createDoctor();
        $admin = $this->createAdmin();
        Passport::actingAs($admin);
        Cache::forget('doctors:cache_version');

        $this->getJson('/api/v1/doctors');

        $this->putJson("/api/v1/doctors/{$doctor->doctor->user_id}", [
            'first_name' => 'PostUpdate',
            'last_name' => $doctor->last_name,
            'username' => $doctor->username,
            'email' => $doctor->email,
            'gender' => $doctor->gender->value,
            'specialization_id' => $this->generalPractitioner->id,
            'experience_months' => 36,
        ])->assertStatus(200);

        $response = $this->getJson('/api/v1/doctors');
        $this->assertEquals('PostUpdate', $response->json('data')[0]['first_name']);
    }

    public function test_doctor_list_cache_invalidated_after_create(): void
    {
        $admin = $this->createAdmin();
        Passport::actingAs($admin);
        Cache::forget('doctors:cache_version');

        $response1 = $this->getJson('/api/v1/doctors');
        $count1 = count($response1->json('data'));

        $this->postJson('/api/v1/doctors', [
            'first_name' => 'Created',
            'last_name' => 'Doctor',
            'username' => 'createddoctor',
            'email' => 'created@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'gender' => 'male',
            'phone' => '1234567890',
            'specialization_id' => $this->generalPractitioner->id,
            'experience_months' => 12,
        ])->assertStatus(201);

        $response2 = $this->getJson('/api/v1/doctors');
        $this->assertCount($count1 + 1, $response2->json('data'));
    }

    public function test_doctor_list_cache_invalidated_after_delete(): void
    {
        $doctor = $this->createDoctor();
        $admin = $this->createAdmin();
        Passport::actingAs($admin);
        Cache::forget('doctors:cache_version');

        $response1 = $this->getJson('/api/v1/doctors');
        $this->assertEquals(1, count($response1->json('data')));

        $this->deleteJson("/api/v1/doctors/{$doctor->doctor->user_id}")->assertStatus(204);

        $response2 = $this->getJson('/api/v1/doctors');
        $this->assertEmpty($response2->json('data'));
    }

    public function test_doctor_ratings_cache_invalidated_when_new_rating_added(): void
    {
        $doctor = $this->createDoctor();
        $patient = User::factory()->create();
        $patient->assignRole('patient');
        $patient->patient()->create([]);
        Passport::actingAs($patient);
        Cache::forget('doctors:cache_version');
        Cache::forget('ratings:cache_version');

        $initial = $this->getJson("/api/v1/doctors/{$doctor->doctor->user_id}/ratings");
        $initialCount = count($initial->json('data'));

        $this->postJson('/api/v1/ratings', [
            'type' => 'user',
            'rateable_id' => $doctor->id,
            'rateable_type' => User::class,
            'rating' => 5,
            'comment' => 'Great doctor!',
        ])->assertStatus(201);

        $response = $this->getJson("/api/v1/doctors/{$doctor->doctor->user_id}/ratings");
        $this->assertCount($initialCount + 1, $response->json('data'));
    }
}
