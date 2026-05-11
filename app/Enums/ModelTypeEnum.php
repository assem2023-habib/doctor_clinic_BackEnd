<?php

namespace App\Enums;

enum ModelTypeEnum: string
{
    case User = 'user';
    case Doctor = 'doctor';
    case Patient = 'patient';
    case Receptionist = 'receptionist';
    case DoctorSchedule = 'doctor_schedule';
    case Appointment = 'appointment';
    case AppointmentStatusLog = 'appointment_status_log';
    case MedicalRecord = 'medical_record';
    case Prescription = 'prescription';
    case PrescriptionItem = 'prescription_item';
    case Medicine = 'medicine';
    case Notification = 'notification';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
