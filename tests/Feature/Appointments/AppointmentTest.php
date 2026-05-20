<?php

namespace Tests\Feature\Appointments;

use App\Domains\Appointments\Models\Appointment;
use App\Domains\Appointments\Models\AppointmentStatusLog;
use App\Enums\AppointmentStatusEnum;
use App\Enums\DayOfWeekEnum;
use App\Enums\GenderEnum;
use App\Domains\Appointments\Enums\PatientResponseEnum;
use App\Enums\SpecializationEnum;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AppointmentTest extends TestCase
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

    private function createPatientUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('patient');
        $user->patient()->create([]);
        return $user;
    }

    private function createDoctorUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('doctor');
        $user->doctor()->create([
            'specialization' => SpecializationEnum::GeneralPractitioner,
            'experience_months' => 24,
        ]);
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

    private function createSchedule($doctor, array $overrides = []): void
    {
        $doctor->schedules()->create(array_merge([
            'day_of_week' => DayOfWeekEnum::Monday,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_active' => true,
        ], $overrides));
    }

    private function createAppointment(array $overrides = []): Appointment
    {
        $patient = $overrides['patient'] ?? $this->createPatientUser();
        $doctor = $overrides['doctor'] ?? $this->createDoctorUser();

        return Appointment::create(array_merge([
            'doctor_id' => $doctor->doctor->id,
            'patient_id' => $patient->patient->id,
            'appointment_date' => now()->addDays(2)->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '10:30',
            'status' => AppointmentStatusEnum::Requested,
            'reason' => 'Checkup',
            'created_by' => $patient->id . ': ' . $patient->first_name . ' ' . $patient->last_name,
        ], $overrides));
    }

    public function test_patient_can_view_available_slots(): void
    {
        $doctorUser = $this->createDoctorUser();
        $this->createSchedule($doctorUser->doctor, ['day_of_week' => DayOfWeekEnum::Monday]);

        $monday = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');

        $response = $this->getJson("/api/v1/doctors/{$doctorUser->doctor->id}/available-slots?date={$monday}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [['start_time', 'end_time']],
            ]);
    }

    public function test_available_slots_returns_empty_for_no_schedule(): void
    {
        $doctorUser = $this->createDoctorUser();

        $monday = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');

        $response = $this->getJson("/api/v1/doctors/{$doctorUser->doctor->id}/available-slots?date={$monday}");

        $response->assertStatus(200);

        $json = $response->json();
        $this->assertEmpty($json['data']);
    }

    public function test_available_slots_excludes_booked_slots(): void
    {
        $doctorUser = $this->createDoctorUser();
        $this->createSchedule($doctorUser->doctor, ['day_of_week' => DayOfWeekEnum::Monday]);
        $patient = $this->createPatientUser();

        $monday = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');

        Appointment::create([
            'doctor_id' => $doctorUser->doctor->id,
            'patient_id' => $patient->patient->id,
            'appointment_date' => $monday,
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => AppointmentStatusEnum::Set,
            'created_by' => $patient->id . ': ' . $patient->first_name . ' ' . $patient->last_name,
        ]);

        $response = $this->getJson("/api/v1/doctors/{$doctorUser->doctor->id}/available-slots?date={$monday}");

        $response->assertStatus(200);

        $slots = $response->json()['data'];
        foreach ($slots as $slot) {
            $overlaps = !($slot['end_time'] <= '09:00' || $slot['start_time'] >= '11:00');
            $this->assertFalse($overlaps, "Slot {$slot['start_time']}-{$slot['end_time']} overlaps with 09:00-11:00");
        }
    }

    public function test_patient_can_request_appointment(): void
    {
        $patient = $this->createPatientUser();
        Passport::actingAs($patient);

        $doctorUser = $this->createDoctorUser();

        $response = $this->postJson('/api/v1/appointments', [
            'doctor_id' => $doctorUser->doctor->id,
            'preferred_date' => now()->addDays(3)->format('Y-m-d'),
            'reason' => 'Feeling unwell',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['id', 'status', 'reason', 'notes', 'doctor', 'patient'],
            ]);

        $json = $response->json();
        $this->assertEquals(AppointmentStatusEnum::Requested->value, $json['data']['status']);
        $this->assertDatabaseHas('appointments', ['id' => $json['data']['id']]);
    }

    public function test_non_patient_cannot_request_appointment(): void
    {
        $doctor = $this->createDoctorUser();
        Passport::actingAs($doctor);

        $response = $this->postJson('/api/v1/appointments', [
            'doctor_id' => $doctor->doctor->id,
            'reason' => 'Feeling unwell',
        ]);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_request_appointment(): void
    {
        $doctorUser = $this->createDoctorUser();

        $response = $this->postJson('/api/v1/appointments', [
            'doctor_id' => $doctorUser->doctor->id,
            'reason' => 'Feeling unwell',
        ]);

        $response->assertStatus(401);
    }

    public function test_staff_can_set_appointment_time(): void
    {
        Queue::fake();

        $admin = $this->createAdminUser();
        Passport::actingAs($admin);

        $appointment = $this->createAppointment();

        $futureDate = now()->addDays(5)->format('Y-m-d');

        $response = $this->postJson("/api/v1/appointments/{$appointment->id}/set-time", [
            'appointment_date' => $futureDate,
            'start_time' => '10:00',
            'end_time' => '10:30',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatusEnum::Set,
        ]);
        $this->assertEquals($futureDate, Appointment::find($appointment->id)->appointment_date->format('Y-m-d'));

        $this->assertDatabaseHas('appointment_status_logs', [
            'appointment_id' => $appointment->id,
            'new_status' => AppointmentStatusEnum::Set,
        ]);
    }

    public function test_doctor_can_set_time_for_own_appointment(): void
    {
        Queue::fake();

        $doctorUser = $this->createDoctorUser();
        Passport::actingAs($doctorUser);

        $patient = $this->createPatientUser();
        $appointment = $this->createAppointment([
            'doctor' => $doctorUser,
            'patient' => $patient,
        ]);

        $futureDate = now()->addDays(5)->format('Y-m-d');

        $response = $this->postJson("/api/v1/appointments/{$appointment->id}/set-time", [
            'appointment_date' => $futureDate,
            'start_time' => '10:00',
            'end_time' => '10:30',
        ]);

        $response->assertStatus(200);
    }

    public function test_non_staff_cannot_set_appointment_time(): void
    {
        $patient = $this->createPatientUser();
        Passport::actingAs($patient);

        $appointment = $this->createAppointment();

        $response = $this->postJson("/api/v1/appointments/{$appointment->id}/set-time", [
            'appointment_date' => now()->addDays(5)->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '10:30',
        ]);

        $response->assertStatus(403);
    }

    public function test_patient_can_accept_appointment(): void
    {
        $patient = $this->createPatientUser();
        Passport::actingAs($patient);

        $doctorUser = $this->createDoctorUser();
        $appointment = $this->createAppointment([
            'patient' => $patient,
            'doctor' => $doctorUser,
            'status' => AppointmentStatusEnum::Set,
        ]);

        $response = $this->postJson("/api/v1/appointments/{$appointment->id}/respond", [
            'response' => PatientResponseEnum::Accepted->value,
        ]);

        $response->assertStatus(200);

        $json = $response->json();
        $this->assertEquals(AppointmentStatusEnum::Accepted->value, $json['data']['status']);

        $this->assertDatabaseHas('appointment_status_logs', [
            'appointment_id' => $appointment->id,
            'new_status' => AppointmentStatusEnum::Accepted,
        ]);
    }

    public function test_patient_can_reject_appointment(): void
    {
        $patient = $this->createPatientUser();
        Passport::actingAs($patient);

        $doctorUser = $this->createDoctorUser();
        $appointment = $this->createAppointment([
            'patient' => $patient,
            'doctor' => $doctorUser,
            'status' => AppointmentStatusEnum::Set,
        ]);

        $response = $this->postJson("/api/v1/appointments/{$appointment->id}/respond", [
            'response' => PatientResponseEnum::Rejected->value,
        ]);

        $response->assertStatus(200);

        $json = $response->json();
        $this->assertEquals(AppointmentStatusEnum::Rejected->value, $json['data']['status']);

        $this->assertDatabaseHas('appointment_status_logs', [
            'appointment_id' => $appointment->id,
            'new_status' => AppointmentStatusEnum::Rejected,
        ]);
    }

    public function test_other_patient_cannot_respond_to_appointment(): void
    {
        $patient = $this->createPatientUser();
        Passport::actingAs($patient);

        $otherPatient = $this->createPatientUser();
        $doctorUser = $this->createDoctorUser();
        $appointment = $this->createAppointment([
            'patient' => $otherPatient,
            'doctor' => $doctorUser,
            'status' => AppointmentStatusEnum::Set,
        ]);

        $response = $this->postJson("/api/v1/appointments/{$appointment->id}/respond", [
            'response' => PatientResponseEnum::Accepted->value,
        ]);

        $response->assertStatus(403);
    }

    public function test_staff_can_cancel_appointment(): void
    {
        $admin = $this->createAdminUser();
        Passport::actingAs($admin);

        $appointment = $this->createAppointment();

        $response = $this->postJson("/api/v1/appointments/{$appointment->id}/cancel");

        $response->assertStatus(200);

        $json = $response->json();
        $this->assertEquals(AppointmentStatusEnum::Cancelled->value, $json['data']['status']);

        $this->assertDatabaseHas('appointment_status_logs', [
            'appointment_id' => $appointment->id,
            'new_status' => AppointmentStatusEnum::Cancelled,
        ]);
    }

    public function test_patient_cannot_cancel_appointment(): void
    {
        $patient = $this->createPatientUser();
        Passport::actingAs($patient);

        $appointment = $this->createAppointment();

        $response = $this->postJson("/api/v1/appointments/{$appointment->id}/cancel");

        $response->assertStatus(403);
    }

    public function test_staff_can_complete_accepted_appointment(): void
    {
        $admin = $this->createAdminUser();
        Passport::actingAs($admin);

        $appointment = $this->createAppointment(['status' => AppointmentStatusEnum::Accepted]);

        $response = $this->postJson("/api/v1/appointments/{$appointment->id}/complete");

        $response->assertStatus(200);

        $json = $response->json();
        $this->assertEquals(AppointmentStatusEnum::Completed->value, $json['data']['status']);
    }

    public function test_cannot_complete_non_accepted_appointment(): void
    {
        $admin = $this->createAdminUser();
        Passport::actingAs($admin);

        $appointment = $this->createAppointment(['status' => AppointmentStatusEnum::Set]);

        $response = $this->postJson("/api/v1/appointments/{$appointment->id}/complete");

        $response->assertStatus(400);
    }

    public function test_staff_can_suggest_alternative(): void
    {
        $admin = $this->createAdminUser();
        Passport::actingAs($admin);

        $appointment = $this->createAppointment();

        $response = $this->postJson("/api/v1/appointments/{$appointment->id}/suggest-alternative", [
            'message' => 'Would next Tuesday work for you?',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
        ]);
    }

    public function test_patient_cannot_suggest_alternative(): void
    {
        $patient = $this->createPatientUser();
        Passport::actingAs($patient);

        $appointment = $this->createAppointment();

        $response = $this->postJson("/api/v1/appointments/{$appointment->id}/suggest-alternative", [
            'message' => 'Can we reschedule?',
        ]);

        $response->assertStatus(403);
    }

    public function test_patient_can_list_own_appointments(): void
    {
        $patient = $this->createPatientUser();
        Passport::actingAs($patient);

        $doctorUser = $this->createDoctorUser();
        $this->createAppointment(['patient' => $patient, 'doctor' => $doctorUser]);

        $response = $this->getJson('/api/v1/appointments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data',
                'meta' => ['pagination'],
            ]);

        $json = $response->json();
        $this->assertCount(1, $json['data']);
    }

    public function test_patient_cannot_see_other_patient_appointments(): void
    {
        $patient = $this->createPatientUser();
        Passport::actingAs($patient);

        $otherPatient = $this->createPatientUser();
        $doctorUser = $this->createDoctorUser();
        $this->createAppointment(['patient' => $otherPatient, 'doctor' => $doctorUser]);

        $response = $this->getJson('/api/v1/appointments');

        $json = $response->json();
        $this->assertCount(0, $json['data']);
    }

    public function test_list_can_filter_by_status(): void
    {
        $admin = $this->createAdminUser();
        Passport::actingAs($admin);

        $doctorUser = $this->createDoctorUser();
        $patient = $this->createPatientUser();
        $this->createAppointment(['patient' => $patient, 'doctor' => $doctorUser, 'status' => AppointmentStatusEnum::Requested]);
        $this->createAppointment(['patient' => $patient, 'doctor' => $doctorUser, 'status' => AppointmentStatusEnum::Accepted]);

        $response = $this->getJson('/api/v1/appointments?status=' . AppointmentStatusEnum::Accepted->value);

        $json = $response->json();
        $this->assertCount(1, $json['data']);
        $this->assertEquals(AppointmentStatusEnum::Accepted->value, $json['data'][0]['status']);
    }

    public function test_patient_can_view_own_appointment(): void
    {
        $patient = $this->createPatientUser();
        Passport::actingAs($patient);

        $doctorUser = $this->createDoctorUser();
        $appointment = $this->createAppointment(['patient' => $patient, 'doctor' => $doctorUser]);

        $response = $this->getJson("/api/v1/appointments/{$appointment->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['id', 'status', 'reason', 'doctor', 'patient'],
            ]);
    }

    public function test_patient_cannot_view_others_appointment(): void
    {
        $patient = $this->createPatientUser();
        Passport::actingAs($patient);

        $otherPatient = $this->createPatientUser();
        $doctorUser = $this->createDoctorUser();
        $appointment = $this->createAppointment(['patient' => $otherPatient, 'doctor' => $doctorUser]);

        $response = $this->getJson("/api/v1/appointments/{$appointment->id}");

        $response->assertStatus(403);
    }

    public function test_admin_can_view_any_appointment(): void
    {
        $admin = $this->createAdminUser();
        Passport::actingAs($admin);

        $patient = $this->createPatientUser();
        $doctorUser = $this->createDoctorUser();
        $appointment = $this->createAppointment(['patient' => $patient, 'doctor' => $doctorUser]);

        $response = $this->getJson("/api/v1/appointments/{$appointment->id}");

        $response->assertStatus(200);
    }

    public function test_set_time_with_overlapping_appointment_fails(): void
    {
        Queue::fake();

        $admin = $this->createAdminUser();
        Passport::actingAs($admin);

        $doctorUser = $this->createDoctorUser();
        $patient = $this->createPatientUser();
        $futureDate = now()->addDays(5)->format('Y-m-d');

        Appointment::create([
            'doctor_id' => $doctorUser->doctor->id,
            'patient_id' => $patient->patient->id,
            'appointment_date' => $futureDate,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => AppointmentStatusEnum::Set,
            'created_by' => $admin->id . ': ' . $admin->first_name . ' ' . $admin->last_name,
        ]);

        $appointment = $this->createAppointment([
            'doctor' => $doctorUser,
            'patient' => $patient,
            'appointment_date' => $futureDate,
        ]);

        $response = $this->postJson("/api/v1/appointments/{$appointment->id}/set-time", [
            'appointment_date' => $futureDate,
            'start_time' => '10:00',
            'end_time' => '10:30',
        ]);

        $response->assertStatus(422);
    }

    public function test_auto_confirm_job_dispatched_on_set_time(): void
    {
        Queue::fake();

        $admin = $this->createAdminUser();
        Passport::actingAs($admin);

        $appointment = $this->createAppointment();
        $futureDate = now()->addDays(5)->format('Y-m-d');

        $response = $this->postJson("/api/v1/appointments/{$appointment->id}/set-time", [
            'appointment_date' => $futureDate,
            'start_time' => '10:00',
            'end_time' => '10:30',
        ]);

        $response->assertStatus(200);

        Queue::assertPushed(\App\Domains\Appointments\Jobs\AutoConfirmAppointment::class);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatusEnum::Set,
        ]);
    }

    public function test_unauthorized_user_cannot_list_appointments(): void
    {
        $response = $this->getJson('/api/v1/appointments');

        $response->assertStatus(401);
    }
}
