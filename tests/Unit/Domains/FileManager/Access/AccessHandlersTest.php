<?php

namespace Tests\Unit\Domains\FileManager\Access;

use App\Domains\FileManager\Access\AdminHandler;
use App\Domains\FileManager\Access\OwnerHandler;
use App\Domains\FileManager\Access\SupervisorDoctorHandler;
use App\Domains\FileManager\Access\TreatingDoctorHandler;
use App\Domains\FileManager\Models\File;
use App\Domains\FileManager\Models\FileDownload;
use App\Domains\MedicalRecords\Models\MedicalRecord;
use App\Domains\Doctors\Models\Doctor;
use App\Domains\Doctors\Models\Specialization;
use App\Domains\Patients\Models\Patient;
use App\Models\User;
use Database\Seeders\SpecializationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccessHandlersTest extends TestCase
{
    use RefreshDatabase;

    private Specialization $generalPractitioner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SpecializationSeeder::class);
        $this->generalPractitioner = Specialization::where('slug', 'general_practitioner')->first();
    }

    #[Test]
    public function owner_handler_grants_access_to_patient(): void
    {
        $patientUser = User::factory()->create();
        $patientUser->assignRole('patient');
        $patientUser->patient()->create([]);

        $doctorUser = User::factory()->create();
        $doctorUser->assignRole('doctor');
        $doctorUser->doctor()->create([
            'specialization_id' => $this->generalPractitioner->id,
            'experience_months' => 24,
        ]);

        $medicalRecord = MedicalRecord::create([
            'patient_id' => $patientUser->patient->id,
            'doctor_id' => $doctorUser->doctor->id,
            'diagnosis' => 'Test',
        ]);

        $file = File::create([
            'user_id' => $patientUser->id,
            'medical_record_id' => $medicalRecord->id,
            'original_name' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
            'path' => 'files/test/test.pdf',
            'file_category' => 'document',
            'total_chunks' => 1,
        ]);

        $handler = new OwnerHandler();
        $result = $handler->handle($patientUser, $file);

        $this->assertTrue($result);
    }

    #[Test]
    public function owner_handler_denies_access_to_non_patient(): void
    {
        $patientUser = User::factory()->create();
        $patientUser->assignRole('patient');
        $patientUser->patient()->create([]);

        $otherUser = User::factory()->create();
        $otherUser->assignRole('patient');
        $otherUser->patient()->create([]);

        $doctorUser = User::factory()->create();

        $medicalRecord = MedicalRecord::create([
            'patient_id' => $patientUser->patient->id,
            'doctor_id' => $doctorUser->id,
            'diagnosis' => 'Test',
        ]);

        $file = File::create([
            'user_id' => $patientUser->id,
            'medical_record_id' => $medicalRecord->id,
            'original_name' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
            'path' => 'files/test/test.pdf',
            'file_category' => 'document',
            'total_chunks' => 1,
        ]);

        $handler = new OwnerHandler();
        $result = $handler->handle($otherUser, $file);

        $this->assertNull($result);
    }

    #[Test]
    public function treating_doctor_handler_grants_access_to_doctor(): void
    {
        $patientUser = User::factory()->create();
        $patientUser->assignRole('patient');
        $patientUser->patient()->create([]);

        $doctorUser = User::factory()->create();
        $doctorUser->assignRole('doctor');
        $doctorUser->doctor()->create([
            'specialization_id' => $this->generalPractitioner->id,
            'experience_months' => 24,
        ]);

        $medicalRecord = MedicalRecord::create([
            'patient_id' => $patientUser->patient->id,
            'doctor_id' => $doctorUser->doctor->id,
            'diagnosis' => 'Test',
        ]);

        $file = File::create([
            'user_id' => $patientUser->id,
            'medical_record_id' => $medicalRecord->id,
            'original_name' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
            'path' => 'files/test/test.pdf',
            'file_category' => 'document',
            'total_chunks' => 1,
        ]);

        $handler = new TreatingDoctorHandler();
        $result = $handler->handle($doctorUser, $file);

        $this->assertTrue($result);
    }

    #[Test]
    public function treating_doctor_handler_denies_access_to_non_doctor(): void
    {
        $patientUser = User::factory()->create();
        $patientUser->assignRole('patient');
        $patientUser->patient()->create([]);

        $doctorUser = User::factory()->create();
        $doctorUser->assignRole('doctor');
        $doctorUser->doctor()->create([
            'specialization_id' => $this->generalPractitioner->id,
            'experience_months' => 24,
        ]);

        $otherDoctorUser = User::factory()->create();
        $otherDoctorUser->assignRole('doctor');
        $otherDoctorUser->doctor()->create([
            'specialization_id' => $this->generalPractitioner->id,
            'experience_months' => 12,
        ]);

        $medicalRecord = MedicalRecord::create([
            'patient_id' => $patientUser->patient->id,
            'doctor_id' => $doctorUser->doctor->id,
            'diagnosis' => 'Test',
        ]);

        $file = File::create([
            'user_id' => $patientUser->id,
            'medical_record_id' => $medicalRecord->id,
            'original_name' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
            'path' => 'files/test/test.pdf',
            'file_category' => 'document',
            'total_chunks' => 1,
        ]);

        $handler = new TreatingDoctorHandler();
        $result = $handler->handle($otherDoctorUser, $file);

        $this->assertNull($result);
    }

    #[Test]
    public function admin_handler_grants_access_to_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $patientUser = User::factory()->create();
        $patientUser->assignRole('patient');
        $patientUser->patient()->create([]);

        $medicalRecord = MedicalRecord::create([
            'patient_id' => $patientUser->patient->id,
            'doctor_id' => $patientUser->id,
            'diagnosis' => 'Test',
        ]);

        $file = File::create([
            'user_id' => $patientUser->id,
            'medical_record_id' => $medicalRecord->id,
            'original_name' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
            'path' => 'files/test/test.pdf',
            'file_category' => 'document',
            'total_chunks' => 1,
        ]);

        $handler = new AdminHandler();
        $result = $handler->handle($admin, $file);

        $this->assertTrue($result);
    }

    #[Test]
    public function admin_handler_denies_access_to_non_admin(): void
    {
        $patientUser = User::factory()->create();
        $patientUser->assignRole('patient');
        $patientUser->patient()->create([]);

        $medicalRecord = MedicalRecord::create([
            'patient_id' => $patientUser->patient->id,
            'doctor_id' => $patientUser->id,
            'diagnosis' => 'Test',
        ]);

        $file = File::create([
            'user_id' => $patientUser->id,
            'medical_record_id' => $medicalRecord->id,
            'original_name' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
            'path' => 'files/test/test.pdf',
            'file_category' => 'document',
            'total_chunks' => 1,
        ]);

        $handler = new AdminHandler();
        $result = $handler->handle($patientUser, $file);

        $this->assertNull($result);
    }

    #[Test]
    public function all_handlers_form_a_chain_and_pass_to_next(): void
    {
        $patientUser = User::factory()->create();
        $patientUser->assignRole('patient');
        $patientUser->patient()->create([]);

        $doctorUser = User::factory()->create();
        $doctorUser->assignRole('doctor');
        $doctorUser->doctor()->create([
            'specialization_id' => $this->generalPractitioner->id,
            'experience_months' => 24,
        ]);

        $medicalRecord = MedicalRecord::create([
            'patient_id' => $patientUser->patient->id,
            'doctor_id' => $doctorUser->doctor->id,
            'diagnosis' => 'Test',
        ]);

        $file = File::create([
            'user_id' => $patientUser->id,
            'medical_record_id' => $medicalRecord->id,
            'original_name' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
            'path' => 'files/test/test.pdf',
            'file_category' => 'document',
            'total_chunks' => 1,
        ]);

        $handler = new OwnerHandler();

        $handler->setNext(new TreatingDoctorHandler())
            ->setNext(new AdminHandler());

        $result = $handler->handle($doctorUser, $file);

        $this->assertTrue($result);
    }

    #[Test]
    public function chain_returns_null_when_no_handler_matches(): void
    {
        $patientUser = User::factory()->create();
        $patientUser->assignRole('patient');
        $patientUser->patient()->create([]);

        $strangerUser = User::factory()->create();
        $strangerUser->assignRole('patient');
        $strangerUser->patient()->create([]);

        $doctorUser = User::factory()->create();
        $doctorUser->assignRole('doctor');
        $doctorUser->doctor()->create([
            'specialization_id' => $this->generalPractitioner->id,
            'experience_months' => 24,
        ]);

        $medicalRecord = MedicalRecord::create([
            'patient_id' => $patientUser->patient->id,
            'doctor_id' => $doctorUser->doctor->id,
            'diagnosis' => 'Test',
        ]);

        $file = File::create([
            'user_id' => $patientUser->id,
            'medical_record_id' => $medicalRecord->id,
            'original_name' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
            'path' => 'files/test/test.pdf',
            'file_category' => 'document',
            'total_chunks' => 1,
        ]);

        $handler = new OwnerHandler();
        $handler->setNext(new TreatingDoctorHandler());

        $result = $handler->handle($strangerUser, $file);

        $this->assertNull($result);
    }
}
