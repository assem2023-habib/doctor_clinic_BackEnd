<?php

namespace Tests\Unit\Domains\FileManager\Services;

use App\Domains\FileManager\Models\File;
use App\Domains\FileManager\Services\FileAccessService;
use App\Domains\MedicalRecords\Models\MedicalRecord;
use App\Domains\Doctors\Models\Specialization;
use App\Models\User;
use Database\Seeders\SpecializationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FileAccessServiceTest extends TestCase
{
    use RefreshDatabase;

    private Specialization $generalPractitioner;

    protected function setUp(): void
    {
        parent::setUp();

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
            'diagnosis' => 'Test',
        ]);
    }

    private function createFile(User $patientUser, MedicalRecord $medicalRecord): File
    {
        return File::create([
            'user_id' => $patientUser->id,
            'medical_record_id' => $medicalRecord->id,
            'original_name' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
            'path' => 'files/test/test.pdf',
            'file_category' => 'document',
            'total_chunks' => 1,
        ]);
    }

    #[Test]
    public function patient_can_access_own_file(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        $medicalRecord = $this->createMedicalRecord($patient, $doctor);
        $file = $this->createFile($patient, $medicalRecord);

        $service = new FileAccessService();
        $result = $service->canAccess($patient, $file);

        $this->assertTrue($result);
    }

    #[Test]
    public function doctor_can_access_patient_file(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        $medicalRecord = $this->createMedicalRecord($patient, $doctor);
        $file = $this->createFile($patient, $medicalRecord);

        $service = new FileAccessService();
        $result = $service->canAccess($doctor, $file);

        $this->assertTrue($result);
    }

    #[Test]
    public function admin_can_access_any_file(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        $admin = $this->createAdminUser();
        $medicalRecord = $this->createMedicalRecord($patient, $doctor);
        $file = $this->createFile($patient, $medicalRecord);

        $service = new FileAccessService();
        $result = $service->canAccess($admin, $file);

        $this->assertTrue($result);
    }

    #[Test]
    public function stranger_cannot_access_file(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        $stranger = $this->createPatientUser();
        $medicalRecord = $this->createMedicalRecord($patient, $doctor);
        $file = $this->createFile($patient, $medicalRecord);

        $service = new FileAccessService();
        $result = $service->canAccess($stranger, $file);

        $this->assertFalse($result);
    }

    #[Test]
    public function supervisor_doctor_can_access_patient_file(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();

        $medicalRecord = $this->createMedicalRecord($patient, $doctor);
        $file = $this->createFile($patient, $medicalRecord);

        $doctor->doctor->patients()->attach($patient->patient->id, [
            'assigned_by' => $doctor->id,
            'supervision_status' => 'approved',
        ]);

        $supervisorDoctorUser = $this->createDoctorUser();
        $supervisorDoctorUser->doctor->patients()->attach($patient->patient->id, [
            'assigned_by' => $doctor->id,
            'supervision_status' => 'approved',
        ]);

        $service = new FileAccessService();
        $result = $service->canAccess($supervisorDoctorUser, $file);

        $this->assertTrue($result);
    }

    #[Test]
    public function non_supervisor_doctor_cannot_access_patient_file(): void
    {
        $patient = $this->createPatientUser();
        $doctor = $this->createDoctorUser();
        $otherDoctor = $this->createDoctorUser();

        $medicalRecord = $this->createMedicalRecord($patient, $doctor);
        $file = $this->createFile($patient, $medicalRecord);

        $service = new FileAccessService();
        $result = $service->canAccess($otherDoctor, $file);

        $this->assertFalse($result);
    }
}
