<?php

namespace Tests\Feature\Files;

use App\Domains\FileManager\Models\File;
use App\Domains\FileManager\Models\FileDownload;
use App\Domains\MedicalRecords\Models\MedicalRecord;
use App\Domains\Doctors\Models\Specialization;
use App\Enums\FileCategoryEnum;
use App\Enums\FileUploadStatusEnum;
use App\Models\User;
use Database\Seeders\SpecializationSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class FileTest extends TestCase
{
    use RefreshDatabase;

    private Specialization $generalPractitioner;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->seed(SpecializationSeeder::class);
        $this->generalPractitioner = Specialization::where('slug', 'general_practitioner')->first();

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
            'specialization_id' => $this->generalPractitioner->id,
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

    private function createMedicalRecord(User $patientUser, User $doctorUser): MedicalRecord
    {
        return MedicalRecord::create([
            'patient_id' => $patientUser->patient->id,
            'doctor_id' => $doctorUser->doctor->id,
            'diagnosis' => 'Test diagnosis',
            'notes' => 'Test notes',
        ]);
    }

    public function test_unauthenticated_user_cannot_upload_file(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->postJson('/api/v1/files', [
            'file' => $file,
            'medical_record_id' => fake()->uuid(),
            'file_category' => FileCategoryEnum::Document->value,
        ]);

        $response->assertStatus(401);
    }

    public function test_can_upload_file_directly(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        Passport::actingAs($patient);

        $medicalRecord = $this->createMedicalRecord($patient, $doctor);
        $file = UploadedFile::fake()->create('test_document.pdf', 100);

        $response = $this->postJson('/api/v1/files', [
            'file' => $file,
            'medical_record_id' => $medicalRecord->id,
            'file_category' => FileCategoryEnum::Document->value,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status', 'message',
                'data' => ['id', 'original_name', 'mime_type', 'size', 'file_category', 'upload_status', 'medical_record_id', 'user_id', 'created_at'],
            ]);

        $json = $response->json();
        $this->assertEquals(FileUploadStatusEnum::Completed->value, $json['data']['upload_status']);
        $this->assertEquals(FileCategoryEnum::Document->value, $json['data']['file_category']);
        $this->assertEquals($medicalRecord->id, $json['data']['medical_record_id']);

        $this->assertDatabaseHas('files', ['id' => $json['data']['id']]);
    }

    public function test_upload_fails_with_invalid_mime_type(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        Passport::actingAs($patient);

        $medicalRecord = $this->createMedicalRecord($patient, $doctor);
        $file = UploadedFile::fake()->create('script.exe', 100);

        $response = $this->postJson('/api/v1/files', [
            'file' => $file,
            'medical_record_id' => $medicalRecord->id,
            'file_category' => FileCategoryEnum::Document->value,
        ]);

        $response->assertStatus(422);
    }

    public function test_upload_fails_with_file_exceeding_max_size(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        Passport::actingAs($patient);

        $medicalRecord = $this->createMedicalRecord($patient, $doctor);
        $maxSize = config('files.max_file_size', 20480);
        $file = UploadedFile::fake()->create('large.pdf', $maxSize + 100);

        $response = $this->postJson('/api/v1/files', [
            'file' => $file,
            'medical_record_id' => $medicalRecord->id,
            'file_category' => FileCategoryEnum::Document->value,
        ]);

        $response->assertStatus(422);
    }

    public function test_can_init_chunked_upload(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        Passport::actingAs($patient);

        $medicalRecord = $this->createMedicalRecord($patient, $doctor);

        $response = $this->postJson('/api/v1/files/init', [
            'medical_record_id' => $medicalRecord->id,
            'file_category' => FileCategoryEnum::LabResult->value,
            'original_name' => 'large_report.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 10240000,
            'total_chunks' => 2,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status', 'message',
                'data' => ['upload_id', 'chunk_size', 'total_chunks'],
            ]);

        $json = $response->json();
        $this->assertDatabaseHas('files', ['id' => $json['data']['upload_id']]);
    }

    public function test_can_upload_chunk(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        Passport::actingAs($patient);

        $medicalRecord = $this->createMedicalRecord($patient, $doctor);

        $initResponse = $this->postJson('/api/v1/files/init', [
            'medical_record_id' => $medicalRecord->id,
            'file_category' => FileCategoryEnum::LabResult->value,
            'original_name' => 'large_report.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 10240000,
            'total_chunks' => 2,
        ]);

        $uploadId = $initResponse->json()['data']['upload_id'];

        $chunk = UploadedFile::fake()->create('chunk1', 5000);
        $response = $this->postJson("/api/v1/files/{$uploadId}/chunk", [
            'chunk' => $chunk,
            'chunk_index' => 0,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => ['received_index' => 0],
            ]);
    }

    public function test_cannot_upload_chunk_to_nonexistent_upload(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        Passport::actingAs($patient);

        $chunk = UploadedFile::fake()->create('chunk1', 100);
        $response = $this->postJson('/api/v1/files/' . fake()->uuid() . '/chunk', [
            'chunk' => $chunk,
            'chunk_index' => 0,
        ]);

        $response->assertStatus(404);
    }

    public function test_can_complete_chunked_upload(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        Passport::actingAs($patient);

        $medicalRecord = $this->createMedicalRecord($patient, $doctor);

        $initResponse = $this->postJson('/api/v1/files/init', [
            'medical_record_id' => $medicalRecord->id,
            'file_category' => FileCategoryEnum::LabResult->value,
            'original_name' => 'large_report.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 200,
            'total_chunks' => 1,
        ]);

        $uploadId = $initResponse->json()['data']['upload_id'];

        $chunkContent = str_repeat('A', 200);
        $chunk = UploadedFile::fake()->createWithContent('chunk0', $chunkContent);
        $this->postJson("/api/v1/files/{$uploadId}/chunk", [
            'chunk' => $chunk,
            'chunk_index' => 0,
        ]);

        $checksum = hash('sha256', $chunkContent);
        $response = $this->postJson("/api/v1/files/{$uploadId}/complete", [
            'checksum' => $checksum,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => ['upload_status' => FileUploadStatusEnum::Completed->value],
            ]);
    }

    public function test_unauthenticated_user_cannot_list_files(): void
    {
        $response = $this->getJson('/api/v1/files');

        $response->assertStatus(401);
    }

    public function test_patient_can_list_own_files(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        Passport::actingAs($patient);

        $medicalRecord = $this->createMedicalRecord($patient, $doctor);

        File::create([
            'user_id' => $patient->id,
            'medical_record_id' => $medicalRecord->id,
            'original_name' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
            'path' => 'files/test/test.pdf',
            'upload_status' => FileUploadStatusEnum::Completed,
            'file_category' => FileCategoryEnum::Document,
            'total_chunks' => 1,
        ]);

        $response = $this->getJson('/api/v1/files');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status', 'message',
                'data' => [['id', 'original_name', 'mime_type', 'size', 'file_category', 'upload_status']],
                'meta' => ['pagination'],
            ]);
    }

    public function test_can_request_download_link(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        Passport::actingAs($patient);

        $medicalRecord = $this->createMedicalRecord($patient, $doctor);

        $storeResponse = $this->postJson('/api/v1/files', [
            'file' => UploadedFile::fake()->create('test.pdf', 100),
            'medical_record_id' => $medicalRecord->id,
            'file_category' => FileCategoryEnum::Document->value,
        ]);

        $fileId = $storeResponse->json()['data']['id'];

        $response = $this->postJson("/api/v1/files/{$fileId}/download-link");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status', 'message',
                'data' => ['url', 'expires_at'],
            ]);
    }

    public function test_doctor_can_request_download_link_for_patient_file(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        Passport::actingAs($patient);

        $medicalRecord = $this->createMedicalRecord($patient, $doctor);

        $storeResponse = $this->postJson('/api/v1/files', [
            'file' => UploadedFile::fake()->create('test.pdf', 100),
            'medical_record_id' => $medicalRecord->id,
            'file_category' => FileCategoryEnum::Document->value,
        ]);

        $fileId = $storeResponse->json()['data']['id'];

        Passport::actingAs($doctor);
        $response = $this->postJson("/api/v1/files/{$fileId}/download-link");

        $response->assertStatus(200);
    }

    public function test_admin_can_request_download_link_for_any_file(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        $admin = $this->createAdminUser();
        Passport::actingAs($patient);

        $medicalRecord = $this->createMedicalRecord($patient, $doctor);

        $storeResponse = $this->postJson('/api/v1/files', [
            'file' => UploadedFile::fake()->create('test.pdf', 100),
            'medical_record_id' => $medicalRecord->id,
            'file_category' => FileCategoryEnum::Document->value,
        ]);

        $fileId = $storeResponse->json()['data']['id'];

        Passport::actingAs($admin);
        $response = $this->postJson("/api/v1/files/{$fileId}/download-link");

        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_cannot_request_download_link(): void
    {
        $response = $this->postJson('/api/v1/files/' . fake()->uuid() . '/download-link');

        $response->assertStatus(401);
    }

    public function test_can_delete_file(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        Passport::actingAs($patient);

        $medicalRecord = $this->createMedicalRecord($patient, $doctor);

        $storeResponse = $this->postJson('/api/v1/files', [
            'file' => UploadedFile::fake()->create('test.pdf', 100),
            'medical_record_id' => $medicalRecord->id,
            'file_category' => FileCategoryEnum::Document->value,
        ]);

        $fileId = $storeResponse->json()['data']['id'];

        $response = $this->deleteJson("/api/v1/files/{$fileId}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('files', ['id' => $fileId]);
    }

    public function test_delete_returns_404_for_nonexistent_file(): void
    {
        $patient = $this->createPatientUser();
        Passport::actingAs($patient);

        $response = $this->deleteJson('/api/v1/files/' . fake()->uuid());

        $response->assertStatus(404);
    }

    public function test_signed_download_logs_download(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        Passport::actingAs($patient);

        $medicalRecord = $this->createMedicalRecord($patient, $doctor);

        $storeResponse = $this->postJson('/api/v1/files', [
            'file' => UploadedFile::fake()->create('test.pdf', 100),
            'medical_record_id' => $medicalRecord->id,
            'file_category' => FileCategoryEnum::Document->value,
        ]);

        $fileId = $storeResponse->json()['data']['id'];

        $linkResponse = $this->postJson("/api/v1/files/{$fileId}/download-link");
        $downloadUrl = $linkResponse->json()['data']['url'];

        $response = $this->getJson($downloadUrl);

        $response->assertStatus(200);

        $this->assertDatabaseHas('file_downloads', [
            'file_id' => $fileId,
        ]);
    }

    public function test_unauthorized_user_cannot_access_file(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        $otherPatient = $this->createPatientUser();
        Passport::actingAs($patient);

        $medicalRecord = $this->createMedicalRecord($patient, $doctor);

        $storeResponse = $this->postJson('/api/v1/files', [
            'file' => UploadedFile::fake()->create('test.pdf', 100),
            'medical_record_id' => $medicalRecord->id,
            'file_category' => FileCategoryEnum::Document->value,
        ]);

        $fileId = $storeResponse->json()['data']['id'];

        Passport::actingAs($otherPatient);
        $response = $this->postJson("/api/v1/files/{$fileId}/download-link");

        $response->assertStatus(403);
    }

    public function test_can_show_file_details(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        Passport::actingAs($patient);

        $medicalRecord = $this->createMedicalRecord($patient, $doctor);

        $storeResponse = $this->postJson('/api/v1/files', [
            'file' => UploadedFile::fake()->create('test.pdf', 100),
            'medical_record_id' => $medicalRecord->id,
            'file_category' => FileCategoryEnum::Document->value,
        ]);

        $fileId = $storeResponse->json()['data']['id'];

        $response = $this->getJson("/api/v1/files/{$fileId}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status', 'message',
                'data' => ['id', 'original_name', 'mime_type', 'size', 'file_category', 'upload_status', 'medical_record_id', 'user_id', 'created_at'],
            ]);
    }

    public function test_init_upload_fails_with_invalid_medical_record(): void
    {
        $patient = $this->createPatientUser();
        Passport::actingAs($patient);

        $response = $this->postJson('/api/v1/files/init', [
            'medical_record_id' => fake()->uuid(),
            'file_category' => FileCategoryEnum::Document->value,
            'original_name' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1000,
            'total_chunks' => 1,
        ]);

        $response->assertStatus(422);
    }

    public function test_init_upload_fails_with_invalid_file_category(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        Passport::actingAs($patient);
        $medicalRecord = $this->createMedicalRecord($patient, $doctor);

        $response = $this->postJson('/api/v1/files/init', [
            'medical_record_id' => $medicalRecord->id,
            'file_category' => 'invalid_category',
            'original_name' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1000,
            'total_chunks' => 1,
        ]);

        $response->assertStatus(422);
    }
}
