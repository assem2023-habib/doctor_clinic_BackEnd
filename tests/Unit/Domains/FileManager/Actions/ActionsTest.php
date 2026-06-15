<?php

namespace Tests\Unit\Domains\FileManager\Actions;

use App\Domains\FileManager\Actions\DeleteFileAction;
use App\Domains\FileManager\Actions\InitChunkedUploadAction;
use App\Domains\FileManager\Actions\RequestDownloadLinkAction;
use App\Domains\FileManager\Actions\StoreFileAction;
use App\Domains\FileManager\Actions\UploadChunkAction;
use App\Domains\FileManager\DTOs\FileData;
use App\Domains\FileManager\Models\File;
use App\Domains\FileManager\Services\ChunkStorageService;
use App\Domains\FileManager\Services\FileAccessService;
use App\Domains\FileManager\Services\FileStorageService;
use App\Domains\MedicalRecords\Models\MedicalRecord;
use App\Domains\Doctors\Models\Specialization;
use App\Enums\FileCategoryEnum;
use App\Enums\FileUploadStatusEnum;
use App\Models\User;
use Database\Seeders\SpecializationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActionsTest extends TestCase
{
    use RefreshDatabase;

    private Specialization $generalPractitioner;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->seed(SpecializationSeeder::class);
        $this->generalPractitioner = Specialization::where('slug', 'general_practitioner')->first();
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

    private function createMedicalRecord(User $patientUser, User $doctorUser): MedicalRecord
    {
        return MedicalRecord::create([
            'patient_id' => $patientUser->patient->id,
            'doctor_id' => $doctorUser->doctor->id,
            'diagnosis' => 'Test',
        ]);
    }

    // --- InitChunkedUploadAction ---

    #[Test]
    public function init_chunked_upload_creates_file_with_uploading_status(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        $medicalRecord = $this->createMedicalRecord($patient, $doctor);

        $fileData = FileData::fromChunkedUpload([
            'medical_record_id' => $medicalRecord->id,
            'file_category' => 'document',
            'original_name' => 'large_file.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 10485760,
        ]);

        $action = new InitChunkedUploadAction();
        $file = $action->execute($fileData, $patient->id);

        $this->assertInstanceOf(File::class, $file);
        $this->assertEquals($patient->id, $file->user_id);
        $this->assertEquals($medicalRecord->id, $file->medical_record_id);
        $this->assertEquals('large_file.pdf', $file->original_name);
        $this->assertEquals('application/pdf', $file->mime_type);
        $this->assertEquals(10485760, $file->size);
        $this->assertEquals(FileUploadStatusEnum::Uploading, $file->upload_status);
        $this->assertEquals(FileCategoryEnum::Document, $file->file_category);
        $this->assertNull($file->path);
        $this->assertEquals(0, $file->total_chunks);

        $this->assertDatabaseHas('files', ['id' => $file->id]);
    }

    #[Test]
    public function init_chunked_upload_accepts_optional_checksum(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        $medicalRecord = $this->createMedicalRecord($patient, $doctor);

        $fileData = FileData::fromChunkedUpload([
            'medical_record_id' => $medicalRecord->id,
            'file_category' => 'xray',
            'original_name' => 'xray.dcm',
            'mime_type' => 'application/dicom',
            'file_size' => 2048,
            'checksum' => 'abcdef0123456789abcdef0123456789abcdef0123456789abcdef0123456789',
        ]);

        $action = new InitChunkedUploadAction();
        $file = $action->execute($fileData, $patient->id);

        $this->assertEquals('abcdef0123456789abcdef0123456789abcdef0123456789abcdef0123456789', $file->checksum);
        $this->assertEquals(FileCategoryEnum::XRay, $file->file_category);
    }

    // --- UploadChunkAction ---

    #[Test]
    public function upload_chunk_stores_chunk_and_updates_counter(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        $medicalRecord = $this->createMedicalRecord($patient, $doctor);

        $file = File::create([
            'user_id' => $patient->id,
            'medical_record_id' => $medicalRecord->id,
            'original_name' => 'big_file.pdf',
            'mime_type' => 'application/pdf',
            'size' => 10000,
            'upload_status' => FileUploadStatusEnum::Uploading,
            'file_category' => 'document',
            'total_chunks' => 0,
        ]);

        $chunk = UploadedFile::fake()->create('chunk0', 5000);

        $chunkStorage = new ChunkStorageService();
        $action = new UploadChunkAction($chunkStorage);

        $result = $action->execute($file, $chunk, 0);

        $this->assertEquals(1, $result->total_chunks);
        $this->assertEquals(FileUploadStatusEnum::Uploading, $result->upload_status);
    }

    #[Test]
    public function upload_chunk_increments_chunk_count_for_subsequent_chunks(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        $medicalRecord = $this->createMedicalRecord($patient, $doctor);

        $file = File::create([
            'user_id' => $patient->id,
            'medical_record_id' => $medicalRecord->id,
            'original_name' => 'big_file.pdf',
            'mime_type' => 'application/pdf',
            'size' => 15000,
            'upload_status' => FileUploadStatusEnum::Uploading,
            'file_category' => 'document',
            'total_chunks' => 0,
        ]);

        $chunkStorage = new ChunkStorageService();
        $action = new UploadChunkAction($chunkStorage);

        $action->execute($file, UploadedFile::fake()->create('chunk0', 5000), 0);
        $result = $action->execute($file->fresh(), UploadedFile::fake()->create('chunk1', 5000), 1);

        $this->assertEquals(2, $result->total_chunks);
    }

    // --- StoreFileAction ---

    #[Test]
    public function store_file_action_creates_file_and_stores_on_disk(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        $medicalRecord = $this->createMedicalRecord($patient, $doctor);

        $uploadedFile = UploadedFile::fake()->create('document.pdf', 200);

        $fileData = FileData::fromDirectUpload([
            'file' => $uploadedFile,
            'medical_record_id' => $medicalRecord->id,
            'file_category' => 'lab_result',
        ]);

        $fileStorage = new FileStorageService();
        $action = new StoreFileAction($fileStorage);

        $file = $action->execute($fileData, $patient->id);

        $this->assertInstanceOf(File::class, $file);
        $this->assertEquals($patient->id, $file->user_id);
        $this->assertEquals($medicalRecord->id, $file->medical_record_id);
        $this->assertEquals('document.pdf', $file->original_name);
        $this->assertEquals(FileUploadStatusEnum::Completed, $file->upload_status);
        $this->assertEquals(FileCategoryEnum::LabResult, $file->file_category);
        $this->assertNotNull($file->path);
        $this->assertNotNull($file->checksum);
        $this->assertEquals(1, $file->total_chunks);

        $this->assertDatabaseHas('files', ['id' => $file->id]);

        Storage::disk('local')->assertExists($file->path);
    }

    #[Test]
    public function store_file_action_stores_file_in_correct_directory(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        $medicalRecord = $this->createMedicalRecord($patient, $doctor);

        $uploadedFile = UploadedFile::fake()->create('xray.jpg', 100);

        $fileData = FileData::fromDirectUpload([
            'file' => $uploadedFile,
            'medical_record_id' => $medicalRecord->id,
            'file_category' => 'xray',
        ]);

        $fileStorage = new FileStorageService();
        $action = new StoreFileAction($fileStorage);

        $file = $action->execute($fileData, $patient->id);

        $this->assertStringStartsWith("files/{$medicalRecord->id}/", $file->path);
        $this->assertStringEndsWith('.jpg', $file->path);
        $this->assertEquals('image/jpeg', $file->mime_type);
    }

    // --- DeleteFileAction ---

    #[Test]
    public function delete_file_action_soft_deletes_file_and_removes_from_storage(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        $medicalRecord = $this->createMedicalRecord($patient, $doctor);

        $uploadedFile = UploadedFile::fake()->create('todelete.pdf', 100);
        $fileData = FileData::fromDirectUpload([
            'file' => $uploadedFile,
            'medical_record_id' => $medicalRecord->id,
            'file_category' => 'document',
        ]);

        $fileStorage = new FileStorageService();
        $storeAction = new StoreFileAction($fileStorage);
        $file = $storeAction->execute($fileData, $patient->id);

        Storage::disk('local')->assertExists($file->path);

        $deleteAction = new DeleteFileAction($fileStorage);
        $deleteAction->execute($file);

        $this->assertSoftDeleted('files', ['id' => $file->id]);
    }

    #[Test]
    public function delete_file_action_handles_file_with_no_path(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        $medicalRecord = $this->createMedicalRecord($patient, $doctor);

        $file = File::create([
            'user_id' => $patient->id,
            'medical_record_id' => $medicalRecord->id,
            'original_name' => 'pending.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
            'path' => null,
            'file_category' => 'document',
            'upload_status' => FileUploadStatusEnum::Failed,
            'total_chunks' => 0,
        ]);

        $fileStorage = new FileStorageService();
        $deleteAction = new DeleteFileAction($fileStorage);
        $deleteAction->execute($file);

        $this->assertSoftDeleted('files', ['id' => $file->id]);
    }

    // --- RequestDownloadLinkAction ---

    #[Test]
    public function request_download_link_returns_signed_url(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        $medicalRecord = $this->createMedicalRecord($patient, $doctor);

        $file = File::create([
            'user_id' => $patient->id,
            'medical_record_id' => $medicalRecord->id,
            'original_name' => 'downloadable.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
            'path' => 'files/test/downloadable.pdf',
            'upload_status' => FileUploadStatusEnum::Completed,
            'file_category' => 'document',
            'total_chunks' => 1,
        ]);

        $accessService = new FileAccessService();
        $action = new RequestDownloadLinkAction($accessService);

        $result = $action->execute($file, $patient);

        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('expires_at', $result);
        $this->assertStringContainsString('/api/v1/files/', $result['url']);
        $this->assertStringContainsString('signature=', $result['url']);
    }

    #[Test]
    public function request_download_link_throws_for_unauthorized_user(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        $stranger = $this->createPatientUser();
        $medicalRecord = $this->createMedicalRecord($patient, $doctor);

        $file = File::create([
            'user_id' => $patient->id,
            'medical_record_id' => $medicalRecord->id,
            'original_name' => 'secret.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
            'path' => 'files/test/secret.pdf',
            'upload_status' => FileUploadStatusEnum::Completed,
            'file_category' => 'document',
            'total_chunks' => 1,
        ]);

        $accessService = new FileAccessService();
        $action = new RequestDownloadLinkAction($accessService);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('You do not have access to this file');

        $action->execute($file, $stranger);
    }

    #[Test]
    public function download_link_expires_at_end_of_day(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        $medicalRecord = $this->createMedicalRecord($patient, $doctor);

        $file = File::create([
            'user_id' => $patient->id,
            'medical_record_id' => $medicalRecord->id,
            'original_name' => 'timed.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
            'path' => 'files/test/timed.pdf',
            'upload_status' => FileUploadStatusEnum::Completed,
            'file_category' => 'document',
            'total_chunks' => 1,
        ]);

        $accessService = new FileAccessService();
        $action = new RequestDownloadLinkAction($accessService);

        $result = $action->execute($file, $patient);

        $expiresAt = new \DateTimeImmutable($result['expires_at']);
        $now = new \DateTimeImmutable();

        $this->assertGreaterThan($now, $expiresAt);
        $this->assertLessThanOrEqual(now()->endOfDay(), $expiresAt);
    }
}
