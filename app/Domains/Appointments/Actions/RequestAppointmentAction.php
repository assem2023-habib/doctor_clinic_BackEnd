<?php

namespace App\Domains\Appointments\Actions;

use App\Domains\Appointments\DTOs\RequestAppointmentData;
use App\Domains\Appointments\Models\Appointment;
use App\Domains\MedicalRecords\Actions\FindOrCreateMedicalRecordAction;
use App\Enums\AppointmentStatusEnum;

class RequestAppointmentAction
{
    public function __construct(
        private readonly FindOrCreateMedicalRecordAction $findOrCreateMedicalRecord,
    ) {}

    public function execute(RequestAppointmentData $data): Appointment
    {
        $medicalRecord = $this->findOrCreateMedicalRecord->execute(
            patientId: $data->patientId,
            doctorId: $data->doctorId,
        );

        $appointment = Appointment::create([
            'doctor_id' => $data->doctorId,
            'patient_id' => $data->patientId,
            'medical_record_id' => $medicalRecord->id,
            'status' => AppointmentStatusEnum::Requested,
            'reason' => $data->reason,
            'notes' => $data->preferredDate ? "Preferred date: {$data->preferredDate}" : null,
            'created_by' => $data->createdBy,
        ]);

        return $appointment;
    }
}
